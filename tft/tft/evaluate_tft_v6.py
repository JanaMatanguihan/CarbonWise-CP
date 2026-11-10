
import pandas as pd
import numpy as np
import matplotlib.pyplot as plt

from pytorch_forecasting import (
    TimeSeriesDataSet,
    TemporalFusionTransformer
)

from sklearn.metrics import (
    mean_absolute_error,
    mean_squared_error,
    r2_score
)


# =====================================================
# SETTINGS
# =====================================================

DATA_PATH = "tft/data/tft_dataset.csv"

MODEL_PATH = "tft/models/tft_model_v6.ckpt"

MAX_ENCODER_LENGTH = 90
MAX_PREDICTION_LENGTH = 30


# =====================================================
# LOAD DATA
# =====================================================

print("Loading CarbonWise dataset...")

df = pd.read_csv(DATA_PATH)

df["record_date"] = pd.to_datetime(df["record_date"])

df = df.sort_values("time_idx").reset_index(drop=True)

df["series"] = "carbon_emissions"


# =====================================================
# CREATE V6 HISTORICAL FEATURES
# =====================================================

print("\nCreating V6 historical features...")

categories = [
    "electricity",
    "transportation",
    "food"
]

for category in categories:

    df[f"{category}_lag_1"] = (
        df[category].shift(1)
    )

    df[f"{category}_lag_7"] = (
        df[category].shift(7)
    )

    df[f"{category}_rolling_7"] = (
        df[category]
        .shift(1)
        .rolling(window=7, min_periods=7)
        .mean()
    )


# Match the feature preparation used during V6 training.

feature_columns = [
    f"{category}_{feature}"
    for category in categories
    for feature in [
        "lag_1",
        "lag_7",
        "rolling_7"
    ]
]

df = df.dropna(
    subset=feature_columns
).reset_index(drop=True)

print("Rows after feature creation:", len(df))


# =====================================================
# CREATE TRAINING DATASET
# =====================================================

training_cutoff = df.loc[
    df["dataset"] == "TRAIN",
    "time_idx"
].max()

training_df = df[
    df["time_idx"] <= training_cutoff
].copy()

unknown_reals = [
    "total_emission",
    "transportation",
    "electricity",
    "food",
    "emission_lag_1",
    "emission_lag_7",
    "emission_lag_14",
    "emission_rolling_7",
    "emission_rolling_30"
]

for category in categories:

    unknown_reals.extend([
        f"{category}_lag_1",
        f"{category}_lag_7",
        f"{category}_rolling_7"
    ])


training = TimeSeriesDataSet(
    training_df,

    time_idx="time_idx",

    target="total_emission",

    group_ids=["series"],

    min_encoder_length=MAX_ENCODER_LENGTH,
    max_encoder_length=MAX_ENCODER_LENGTH,

    min_prediction_length=MAX_PREDICTION_LENGTH,
    max_prediction_length=MAX_PREDICTION_LENGTH,

    static_categoricals=[
        "series"
    ],

    time_varying_known_reals=[
        "time_idx",
        "day_of_week",
        "month",
        "day_of_month",
        "is_weekend"
    ],

    time_varying_unknown_reals=unknown_reals,

    add_relative_time_idx=True,
    add_target_scales=True,
    add_encoder_length=True,

    allow_missing_timesteps=False,
)


# =====================================================
# VALIDATION DATA
# =====================================================

validation_df = df[
    df["dataset"] == "VALIDATION"
].copy()

validation_df = validation_df.sort_values(
    "record_date"
).reset_index(drop=True)

print("\nValidation period:")
print("First date:", validation_df["record_date"].min())
print("Last date:", validation_df["record_date"].max())
print("Validation days:", len(validation_df))


# =====================================================
# LOAD V6 MODEL
# =====================================================

print("\nLoading trained TFT V6 model...")

model = TemporalFusionTransformer.load_from_checkpoint(
    MODEL_PATH
)

model.eval()


# =====================================================
# ROLLING 30-DAY VALIDATION
# =====================================================

print("\n========================================")
print("TFT V6 ROLLING 30-DAY VALIDATION")
print("========================================")

all_actuals = []
all_predictions = []
all_dates = []

window_results = []

window_number = 1

for start_idx in range(
    0,
    len(validation_df) - MAX_PREDICTION_LENGTH + 1,
    MAX_PREDICTION_LENGTH
):

    end_idx = start_idx + MAX_PREDICTION_LENGTH

    window_validation = validation_df.iloc[
        start_idx:end_idx
    ].copy()

    window_start = window_validation[
        "record_date"
    ].min()

    window_end = window_validation[
        "record_date"
    ].max()

    prediction_end_time_idx = window_validation[
        "time_idx"
    ].max()

    minimum_time_idx = (
        window_validation["time_idx"].min()
        - MAX_ENCODER_LENGTH
    )

    prediction_data = df[
        (df["time_idx"] >= minimum_time_idx)
        &
        (df["time_idx"] <= prediction_end_time_idx)
    ].copy()

    try:

        prediction_dataset = TimeSeriesDataSet.from_dataset(
            training,
            prediction_data,
            predict=True,
            stop_randomization=True
        )

        prediction_dataloader = prediction_dataset.to_dataloader(
            train=False,
            batch_size=1,
            num_workers=0
        )

        prediction_output = model.predict(
            prediction_dataloader,
            mode="prediction"
        )

        predictions = (
            prediction_output
            .detach()
            .cpu()
            .numpy()
        )

        predictions = predictions[0]

        actual = window_validation[
            "total_emission"
        ].values

        if len(predictions) != MAX_PREDICTION_LENGTH:

            print(
                f"Skipping window {window_number}: "
                f"prediction length = {len(predictions)}"
            )

            continue

        window_mae = mean_absolute_error(
            actual,
            predictions
        )

        window_rmse = np.sqrt(
            mean_squared_error(
                actual,
                predictions
            )
        )

        window_r2 = r2_score(
            actual,
            predictions
        )

        print(
            f"\nWindow {window_number}: "
            f"{window_start.date()} -> {window_end.date()}"
        )

        print(
            f"MAE: {window_mae:.2f} | "
            f"RMSE: {window_rmse:.2f} | "
            f"R2: {window_r2:.4f}"
        )

        all_actuals.extend(actual)

        all_predictions.extend(predictions)

        all_dates.extend(
            window_validation["record_date"].values
        )

        window_results.append({
            "window": window_number,
            "start_date": window_start,
            "end_date": window_end,
            "mae": window_mae,
            "rmse": window_rmse,
            "r2": window_r2
        })

        window_number += 1

    except Exception as e:

        print(
            f"\nWindow {window_number} failed:"
        )

        print(e)

        continue


# =====================================================
# CHECK RESULTS
# =====================================================

if len(all_actuals) == 0:

    print("\nERROR: No validation predictions were generated.")

    raise SystemExit


# =====================================================
# OVERALL METRICS
# =====================================================

all_actuals = np.array(all_actuals)

all_predictions = np.array(all_predictions)

overall_mae = mean_absolute_error(
    all_actuals,
    all_predictions
)

overall_rmse = np.sqrt(
    mean_squared_error(
        all_actuals,
        all_predictions
    )
)

overall_r2 = r2_score(
    all_actuals,
    all_predictions
)


print("\n========================================")
print("TFT V6 MULTI-WINDOW VALIDATION RESULTS")
print("========================================")

print("Validation windows:", len(window_results))

print("Prediction days evaluated:", len(all_actuals))

print(f"MAE:  {overall_mae:.2f}")

print(f"RMSE: {overall_rmse:.2f}")

print(f"R2:   {overall_r2:.4f}")


# =====================================================
# SAVE WINDOW METRICS
# =====================================================

window_results_df = pd.DataFrame(
    window_results
)

window_metrics_path = (
    "tft/data/tft_v6_validation_window_metrics.csv"
)

window_results_df.to_csv(
    window_metrics_path,
    index=False
)

print("\nV6 window metrics saved to:")

print(window_metrics_path)


# =====================================================
# SAVE PREDICTIONS
# =====================================================

results = pd.DataFrame({
    "record_date": all_dates,
    "actual_emission": all_actuals,
    "predicted_emission": all_predictions
})

output_path = (
    "tft/data/tft_v6_validation_predictions.csv"
)

results.to_csv(
    output_path,
    index=False
)

print("\nV6 predictions saved to:")

print(output_path)


# =====================================================
# PLOT RESULTS
# =====================================================

plt.figure(figsize=(14, 6))

plt.plot(
    results["record_date"],
    results["actual_emission"],
    label="Actual"
)

plt.plot(
    results["record_date"],
    results["predicted_emission"],
    label="TFT V6 Predicted"
)

plt.title(
    "TFT V6: Actual vs Predicted Carbon Emissions"
)

plt.xlabel("Date")

plt.ylabel("Total Emission (kg CO2e)")

plt.legend()

plt.xticks(rotation=45)

plt.tight_layout()

plot_path = (
    "tft/data/tft_v6_validation_plot.png"
)

plt.savefig(
    plot_path,
    dpi=150
)

plt.close()

print("\nV6 plot saved to:")

print(plot_path)
