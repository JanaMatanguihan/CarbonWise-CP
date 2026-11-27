
import os
import numpy as np
import pandas as pd

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

ORIGINAL_DATA_PATH = "data/tft_dataset.csv"

V2_DATA_PATH = "data/tft_dataset_electricity_v2.csv"

ORIGINAL_MODEL_PATH = "models/tft_model_electricity.ckpt"

V2_MODEL_PATH = "models/electricity_v2_best.ckpt"

MAX_ENCODER_LENGTH = 90
MAX_PREDICTION_LENGTH = 30

BATCH_SIZE = 1

PREDICTIONS_PATH = (
    "data/electricity_v2_comparison_predictions.csv"
)

METRICS_PATH = (
    "data/electricity_v2_comparison_metrics.csv"
)


# ============================================================
# CHECK FILES
# ============================================================

print("\n========================================")
print("ELECTRICITY TFT V2 MODEL COMPARISON")
print("========================================")

required_files = [
    ORIGINAL_DATA_PATH,
    V2_DATA_PATH,
    ORIGINAL_MODEL_PATH,
    V2_MODEL_PATH,
]

for path in required_files:

    if not os.path.exists(path):
        raise FileNotFoundError(
            f"Required file not found: {path}"
        )

    print("Found:", path)


# ============================================================
# LOAD DATASETS
# ============================================================

print("\nLoading original dataset...")

original_df = pd.read_csv(ORIGINAL_DATA_PATH)

original_df["record_date"] = pd.to_datetime(
    original_df["record_date"]
)

original_df["time_idx"] = (
    original_df["time_idx"].astype(int)
)

original_df["series"] = "carbon_emissions"

original_df = original_df.sort_values(
    "time_idx"
).reset_index(drop=True)


print("\nLoading revised electricity dataset...")

v2_df = pd.read_csv(V2_DATA_PATH)

v2_df["record_date"] = pd.to_datetime(
    v2_df["record_date"]
)

v2_df["time_idx"] = (
    v2_df["time_idx"].astype(int)
)

v2_df["series"] = "electricity"

v2_df = v2_df.sort_values(
    "time_idx"
).reset_index(drop=True)


# ============================================================
# GET VALIDATION DATA
# ============================================================

original_validation = original_df[
    original_df["dataset"] == "VALIDATION"
].copy()

v2_validation = v2_df[
    v2_df["dataset"] == "VALIDATION"
].copy()

if len(original_validation) != len(v2_validation):
    raise ValueError(
        "Original and revised validation row counts differ."
    )

if not original_validation["record_date"].reset_index(
    drop=True
).equals(
    v2_validation["record_date"].reset_index(drop=True)
):
    raise ValueError(
        "Original and revised validation dates do not match."
    )

print("\nValidation boundaries verified.")

print(
    "Validation period:",
    original_validation["record_date"].min().date(),
    "to",
    original_validation["record_date"].max().date(),
)

print("Validation days:", len(original_validation))


# ============================================================
# CREATE ORIGINAL TRAINING DATASET
# ============================================================

print("\nCreating original model dataset...")

original_train_df = original_df[
    original_df["dataset"] == "TRAIN"
].copy()

original_training = TimeSeriesDataSet(
    original_train_df,

    time_idx="time_idx",

    target="electricity",

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
# CREATE REVISED V2 TRAINING DATASET
# ============================================================

print("\nCreating revised electricity V2 dataset...")

v2_train_df = v2_df[
    v2_df["dataset"] == "TRAIN"
].copy()

electricity_features = [
    "electricity_lag_1",
    "electricity_lag_7",
    "electricity_lag_14",
    "electricity_rolling_7",
    "electricity_rolling_30",
]

v2_training = TimeSeriesDataSet(
    v2_train_df,

    time_idx="time_idx",

    target="electricity",

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
        "electricity",
        *electricity_features,
    ],

    add_relative_time_idx=True,
    add_target_scales=True,
    add_encoder_length=True,

    allow_missing_timesteps=False,
)


# ============================================================
# LOAD MODELS
# ============================================================

print("\nLoading original electricity model...")

original_model = (
    TemporalFusionTransformer.load_from_checkpoint(
        ORIGINAL_MODEL_PATH,
        map_location="cpu",
    )
)

original_model.eval()

print("Original model loaded.")


print("\nLoading best Electricity V2 model...")

v2_model = (
    TemporalFusionTransformer.load_from_checkpoint(
        V2_MODEL_PATH,
        map_location="cpu",
    )
)

v2_model.eval()

print("Electricity V2 model loaded.")


# ============================================================
# PREDICTION FUNCTION
# ============================================================

def generate_predictions(
    model,
    training_dataset,
    prediction_data,
    model_name,
):

    prediction_dataset = TimeSeriesDataSet.from_dataset(
        training_dataset,

        prediction_data,

        predict=True,

        stop_randomization=True,

        min_prediction_length=MAX_PREDICTION_LENGTH,

        max_prediction_length=MAX_PREDICTION_LENGTH,
    )

    if len(prediction_dataset) != 1:
        raise ValueError(
            f"{model_name}: Expected one prediction sample, "
            f"got {len(prediction_dataset)}."
        )

    prediction_dataloader = prediction_dataset.to_dataloader(
        train=False,
        batch_size=BATCH_SIZE,
        num_workers=0,
    )

    output = model.predict(
        prediction_dataloader,
        mode="prediction",
    )

    predictions = (
        output.detach().cpu().numpy()
    )

    predictions = np.asarray(predictions).squeeze()

    if predictions.ndim != 1:
        raise ValueError(
            f"{model_name}: Unexpected prediction shape "
            f"{predictions.shape}."
        )

    if len(predictions) != MAX_PREDICTION_LENGTH:
        raise ValueError(
            f"{model_name}: Expected "
            f"{MAX_PREDICTION_LENGTH} predictions, "
            f"got {len(predictions)}."
        )

    return predictions


# ============================================================
# ROLLING VALIDATION
# ============================================================

print("\n========================================")
print("RUNNING SIX VALIDATION WINDOWS")
print("========================================")

all_predictions = []
window_metrics = []

for window_number, start_idx in enumerate(
    range(
        0,
        len(original_validation)
        - MAX_PREDICTION_LENGTH + 1,
        MAX_PREDICTION_LENGTH,
    ),
    start=1,
):

    end_idx = start_idx + MAX_PREDICTION_LENGTH

    original_window = original_validation.iloc[
        start_idx:end_idx
    ].copy()

    v2_window = v2_validation.iloc[
        start_idx:end_idx
    ].copy()

    window_start = original_window[
        "record_date"
    ].min()

    window_end = original_window[
        "record_date"
    ].max()

    start_time_idx = int(
        original_window["time_idx"].min()
    )

    end_time_idx = int(
        original_window["time_idx"].max()
    )

    minimum_time_idx = (
        start_time_idx - MAX_ENCODER_LENGTH
    )

    # Historical encoder + current 30-day prediction window.
    original_prediction_data = original_df[
        (original_df["time_idx"] >= minimum_time_idx)
        & (original_df["time_idx"] <= end_time_idx)
    ].copy()

    v2_prediction_data = v2_df[
        (v2_df["time_idx"] >= minimum_time_idx)
        & (v2_df["time_idx"] <= end_time_idx)
    ].copy()

    print("\n----------------------------------------")
    print(
        f"WINDOW {window_number}: "
        f"{window_start.date()} to {window_end.date()}"
    )
    print("----------------------------------------")

    # --------------------------------------------------------
    # ORIGINAL MODEL
    # --------------------------------------------------------

    original_predictions = generate_predictions(
        original_model,
        original_training,
        original_prediction_data,
        "Original Electricity TFT",
    )

    # --------------------------------------------------------
    # REVISED V2 MODEL
    # --------------------------------------------------------

    v2_predictions = generate_predictions(
        v2_model,
        v2_training,
        v2_prediction_data,
        "Electricity TFT V2",
    )

    # --------------------------------------------------------
    # ACTUAL VALUES
    # --------------------------------------------------------

    actual = original_window[
        "electricity"
    ].to_numpy(dtype=float)

    if not np.allclose(
        actual,
        v2_window["electricity"].to_numpy(dtype=float),
    ):
        raise ValueError(
            "Actual electricity values do not match "
            "between datasets."
        )

    # --------------------------------------------------------
    # METRICS
    # --------------------------------------------------------

    original_mae = mean_absolute_error(
        actual,
        original_predictions,
    )

    original_rmse = np.sqrt(
        mean_squared_error(
            actual,
            original_predictions,
        )
    )

    original_r2 = r2_score(
        actual,
        original_predictions,
    )

    v2_mae = mean_absolute_error(
        actual,
        v2_predictions,
    )

    v2_rmse = np.sqrt(
        mean_squared_error(
            actual,
            v2_predictions,
        )
    )

    v2_r2 = r2_score(
        actual,
        v2_predictions,
    )

    original_bias = np.mean(
        original_predictions - actual
    )

    v2_bias = np.mean(
        v2_predictions - actual
    )

    print("\nORIGINAL ELECTRICITY TFT")

    print(f"MAE:  {original_mae:.2f}")
    print(f"RMSE: {original_rmse:.2f}")
    print(f"R2:   {original_r2:.4f}")

    print("\nREVISED ELECTRICITY TFT V2")

    print(f"MAE:  {v2_mae:.2f}")
    print(f"RMSE: {v2_rmse:.2f}")
    print(f"R2:   {v2_r2:.4f}")

    print(
        "\nMAE improvement:",
        f"{original_mae - v2_mae:.2f}"
    )

    # --------------------------------------------------------
    # SAVE WINDOW METRICS
    # --------------------------------------------------------

    window_metrics.append({

        "window": window_number,

        "start_date": window_start.date(),

        "end_date": window_end.date(),

        "original_mae": original_mae,

        "original_rmse": original_rmse,

        "original_r2": original_r2,

        "original_bias": original_bias,

        "v2_mae": v2_mae,

        "v2_rmse": v2_rmse,

        "v2_r2": v2_r2,

        "v2_bias": v2_bias,

        "mae_improvement": original_mae - v2_mae,

    })

    # --------------------------------------------------------
    # SAVE DAILY PREDICTIONS
    # --------------------------------------------------------

    for i in range(MAX_PREDICTION_LENGTH):

        all_predictions.append({

            "record_date": original_window[
                "record_date"
            ].iloc[i],

            "actual_electricity": actual[i],

            "original_prediction": (
                original_predictions[i]
            ),

            "v2_prediction": (
                v2_predictions[i]
            ),

            "original_absolute_error": abs(
                actual[i] - original_predictions[i]
            ),

            "v2_absolute_error": abs(
                actual[i] - v2_predictions[i]
            ),

        })


# ============================================================
# OVERALL RESULTS
# ============================================================

results_df = pd.DataFrame(all_predictions)

actual_all = results_df[
    "actual_electricity"
].to_numpy()

original_all = results_df[
    "original_prediction"
].to_numpy()

v2_all = results_df[
    "v2_prediction"
].to_numpy()

overall_original_mae = mean_absolute_error(
    actual_all,
    original_all,
)

overall_original_rmse = np.sqrt(
    mean_squared_error(
        actual_all,
        original_all,
    )
)

overall_original_r2 = r2_score(
    actual_all,
    original_all,
)

overall_v2_mae = mean_absolute_error(
    actual_all,
    v2_all,
)

overall_v2_rmse = np.sqrt(
    mean_squared_error(
        actual_all,
        v2_all,
    )
)

overall_v2_r2 = r2_score(
    actual_all,
    v2_all,
)

overall_mae_improvement = (
    overall_original_mae - overall_v2_mae
)

improvement_percentage = (
    overall_mae_improvement
    / overall_original_mae
    * 100
)


# ============================================================
# DISPLAY OVERALL RESULTS
# ============================================================

print("\n========================================")
print("OVERALL ELECTRICITY MODEL COMPARISON")
print("========================================")

print("\nORIGINAL ELECTRICITY TFT")

print(f"MAE:  {overall_original_mae:.2f}")
print(f"RMSE: {overall_original_rmse:.2f}")
print(f"R2:   {overall_original_r2:.4f}")

print("\nREVISED ELECTRICITY TFT V2")

print(f"MAE:  {overall_v2_mae:.2f}")
print(f"RMSE: {overall_v2_rmse:.2f}")
print(f"R2:   {overall_v2_r2:.4f}")

print("\n========================================")
print("IMPROVEMENT")
print("========================================")

print(
    f"MAE improvement: "
    f"{overall_mae_improvement:.2f}"
)

print(
    f"MAE improvement percentage: "
    f"{improvement_percentage:.2f}%"
)

if overall_v2_mae < overall_original_mae:
    print("\nElectricity TFT V2 has a lower overall MAE.")
else:
    print("\nOriginal Electricity TFT has a lower or equal overall MAE.")


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
    PREDICTIONS_PATH,
    index=False,
)

print("\n========================================")
print("EVALUATION COMPLETED")
print("========================================")

print("\nDaily predictions saved:")
print(PREDICTIONS_PATH)

print("\nWindow metrics saved:")
print(METRICS_PATH)

print("\nTotal prediction days:", len(results_df))
print("Validation windows:", len(metrics_df))
