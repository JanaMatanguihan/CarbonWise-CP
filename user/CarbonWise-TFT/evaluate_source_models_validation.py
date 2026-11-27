
import os
import pandas as pd
import numpy as np

from pytorch_forecasting import (
    TemporalFusionTransformer,
    TimeSeriesDataSet,
)

from sklearn.metrics import (
    mean_absolute_error,
    mean_squared_error,
    r2_score,
)


# ============================================================
# SETTINGS
# ============================================================

DATA_PATH = "data/tft_dataset.csv"

MAX_ENCODER_LENGTH = 90
MAX_PREDICTION_LENGTH = 30

BATCH_SIZE = 1

SOURCE_MODELS = {
    "transportation": "models/tft_model_transportation.ckpt",
    "electricity": "models/tft_model_electricity.ckpt",
    "food": "models/tft_model_food.ckpt",
}

OUTPUT_PATH = "data/category_tft_validation_predictions.csv"
METRICS_PATH = "data/category_tft_validation_metrics.csv"


# ============================================================
# LOAD DATA
# ============================================================

print("\nLoading CarbonWise dataset...")

df = pd.read_csv(DATA_PATH)

df["record_date"] = pd.to_datetime(df["record_date"])

df["time_idx"] = df["time_idx"].astype(int)

df["series"] = "carbon_emissions"

df = df.sort_values("time_idx").reset_index(drop=True)

train_df = df[
    df["dataset"] == "TRAIN"
].copy()

validation_df = df[
    df["dataset"] == "VALIDATION"
].copy()

print("Total rows:", len(df))
print("Training rows:", len(train_df))
print("Validation rows:", len(validation_df))

print(
    "Validation period:",
    validation_df["record_date"].min(),
    "to",
    validation_df["record_date"].max(),
)


# ============================================================
# CHECK CHECKPOINTS
# ============================================================

for target, path in SOURCE_MODELS.items():

    if not os.path.exists(path):
        raise FileNotFoundError(
            f"Missing checkpoint for {target}: {path}"
        )

print("\nAll three category checkpoints were found.")


# ============================================================
# CREATE CATEGORY DATASET
# ============================================================

def create_training_dataset(target_column):

    return TimeSeriesDataSet(
        train_df,

        time_idx="time_idx",

        target=target_column,

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
            "is_weekend",
        ],

        time_varying_unknown_reals=[
            "total_emission",
            "transportation",
            "electricity",
            "food",
            "emission_lag_1",
            "emission_lag_7",
            "emission_lag_14",
            "emission_rolling_7",
            "emission_rolling_30",
        ],

        add_relative_time_idx=True,
        add_target_scales=True,
        add_encoder_length=True,

        allow_missing_timesteps=False,
    )


# ============================================================
# LOAD CATEGORY MODELS
# ============================================================

models = {}
training_datasets = {}

print("\nLoading category models...")

for target, path in SOURCE_MODELS.items():

    print(f"\nLoading {target} model...")

    training_datasets[target] = create_training_dataset(
        target
    )

    models[target] = (
        TemporalFusionTransformer.load_from_checkpoint(
            path,
            map_location="cpu",
        )
    )

    models[target].eval()

    print(f"{target} model loaded successfully.")


# ============================================================
# ROLLING VALIDATION
# ============================================================

all_results = []
window_metrics = []

window_number = 1

print("\n========================================")
print("CATEGORY TFT ROLLING VALIDATION")
print("========================================")

for start_idx in range(
    0,
    len(validation_df) - MAX_PREDICTION_LENGTH + 1,
    MAX_PREDICTION_LENGTH,
):

    end_idx = start_idx + MAX_PREDICTION_LENGTH

    window = validation_df.iloc[
        start_idx:end_idx
    ].copy()

    window_start = window["record_date"].min()
    window_end = window["record_date"].max()

    start_time_idx = int(window["time_idx"].min())
    end_time_idx = int(window["time_idx"].max())

    minimum_time_idx = (
        start_time_idx - MAX_ENCODER_LENGTH
    )

    prediction_data = df[
        (df["time_idx"] >= minimum_time_idx)
        & (df["time_idx"] <= end_time_idx)
    ].copy()

    category_predictions = {}

    print(
        f"\nWindow {window_number}: "
        f"{window_start.date()} to {window_end.date()}"
    )

    # --------------------------------------------------------
    # PREDICT EACH CATEGORY
    # --------------------------------------------------------

    for target in SOURCE_MODELS:

        prediction_dataset = TimeSeriesDataSet.from_dataset(
            training_datasets[target],
            prediction_data,
            predict=True,
            stop_randomization=True,
        )

        if len(prediction_dataset) != 1:
            raise ValueError(
                f"{target}: Expected one prediction window, "
                f"got {len(prediction_dataset)}."
            )

        prediction_dataloader = prediction_dataset.to_dataloader(
            train=False,
            batch_size=BATCH_SIZE,
            num_workers=0,
        )

        prediction_output = models[target].predict(
            prediction_dataloader,
            mode="prediction",
        )

        predictions = (
            prediction_output
            .detach()
            .cpu()
            .numpy()
        )

        predictions = predictions[0]

        if len(predictions) != MAX_PREDICTION_LENGTH:
            raise ValueError(
                f"{target}: Expected 30 predictions, "
                f"got {len(predictions)}."
            )

        category_predictions[target] = predictions

        print(
            f"{target} average prediction: "
            f"{predictions.mean():.2f}"
        )

    # --------------------------------------------------------
    # COMBINE CATEGORY PREDICTIONS
    # --------------------------------------------------------

    predicted_total = (
        category_predictions["transportation"]
        + category_predictions["electricity"]
        + category_predictions["food"]
    )

    actual_total = window[
        "total_emission"
    ].to_numpy()

    if len(predicted_total) != len(actual_total):
        raise ValueError(
            "Combined prediction and actual lengths differ."
        )

    # --------------------------------------------------------
    # METRICS
    # --------------------------------------------------------

    mae = mean_absolute_error(
        actual_total,
        predicted_total,
    )

    rmse = np.sqrt(
        mean_squared_error(
            actual_total,
            predicted_total,
        )
    )

    r2 = r2_score(
        actual_total,
        predicted_total,
    )

    print(f"Combined MAE:  {mae:.2f}")
    print(f"Combined RMSE: {rmse:.2f}")
    print(f"Combined R2:   {r2:.4f}")

    window_metrics.append({
        "window": window_number,
        "start_date": window_start,
        "end_date": window_end,
        "mae": mae,
        "rmse": rmse,
        "r2": r2,
    })

    # --------------------------------------------------------
    # SAVE DAILY PREDICTIONS
    # --------------------------------------------------------

    for i in range(MAX_PREDICTION_LENGTH):

        all_results.append({
            "record_date": window["record_date"].iloc[i],

            "actual_total": actual_total[i],

            "actual_transportation":
                window["transportation"].iloc[i],

            "actual_electricity":
                window["electricity"].iloc[i],

            "actual_food":
                window["food"].iloc[i],

            "predicted_transportation":
                category_predictions["transportation"][i],

            "predicted_electricity":
                category_predictions["electricity"][i],

            "predicted_food":
                category_predictions["food"][i],

            "predicted_total":
                predicted_total[i],
        })

    window_number += 1


# ============================================================
# OVERALL RESULTS
# ============================================================

results_df = pd.DataFrame(all_results)

actual = results_df["actual_total"].to_numpy()

predicted = results_df["predicted_total"].to_numpy()

overall_mae = mean_absolute_error(
    actual,
    predicted,
)

overall_rmse = np.sqrt(
    mean_squared_error(
        actual,
        predicted,
    )
)

overall_r2 = r2_score(
    actual,
    predicted,
)

print("\n========================================")
print("CATEGORY TFT OVERALL VALIDATION RESULTS")
print("========================================")

print("Validation windows:", len(window_metrics))
print("Prediction days:", len(results_df))

print(f"MAE:  {overall_mae:.2f}")
print(f"RMSE: {overall_rmse:.2f}")
print(f"R2:   {overall_r2:.4f}")


# ============================================================
# SAVE RESULTS
# ============================================================

os.makedirs("data", exist_ok=True)

metrics_df = pd.DataFrame(window_metrics)

metrics_df.to_csv(
    METRICS_PATH,
    index=False,
)

results_df.to_csv(
    OUTPUT_PATH,
    index=False,
)

print("\nSaved daily predictions:")
print(OUTPUT_PATH)

print("\nSaved window metrics:")
print(METRICS_PATH)

print("\n========================================")
print("CATEGORY TFT EVALUATION COMPLETED")
print("========================================")
