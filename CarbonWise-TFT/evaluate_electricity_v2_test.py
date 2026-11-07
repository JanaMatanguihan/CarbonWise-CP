
import os
import numpy as np
import pandas as pd

from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from pytorch_forecasting import TimeSeriesDataSet, TemporalFusionTransformer


# ============================================================
# CONFIGURATION
# ============================================================

ORIGINAL_DATA_PATH = "data/tft_dataset.csv"
V2_DATA_PATH = "data/tft_dataset_electricity_v2.csv"

ORIGINAL_MODEL_PATH = "models/tft_model_electricity.ckpt"
V2_MODEL_PATH = "models/electricity_v2_best.ckpt"

PREDICTIONS_OUTPUT = "data/electricity_v2_test_predictions.csv"
METRICS_OUTPUT = "data/electricity_v2_test_metrics.csv"

TEST_START = "2026-07-01"
TEST_END = "2026-07-20"

ENCODER_LENGTH = 90
PREDICTION_LENGTH = 20

KNOWN_REALS = [
    "time_idx",
    "day_of_week",
    "month",
    "day_of_month",
    "is_weekend",
]

ORIGINAL_UNKNOWN_REALS = [
    "total_emission",
    "transportation",
    "electricity",
    "food",
    "emission_lag_1",
    "emission_lag_7",
    "emission_lag_14",
    "emission_rolling_7",
    "emission_rolling_30",
]

V2_UNKNOWN_REALS = [
    "electricity",
    "electricity_lag_1",
    "electricity_lag_7",
    "electricity_lag_14",
    "electricity_rolling_7",
    "electricity_rolling_30",
]


# ============================================================
# LOAD DATA
# ============================================================

def load_data(path, series_name):
    if not os.path.exists(path):
        raise FileNotFoundError(f"Dataset not found: {path}")

    df = pd.read_csv(path)
    df["record_date"] = pd.to_datetime(df["record_date"])
    df["series"] = series_name

    return df.sort_values("time_idx").reset_index(drop=True)


original_df = load_data(
    ORIGINAL_DATA_PATH,
    "carbon_emissions"
)

v2_df = load_data(
    V2_DATA_PATH,
    "electricity"
)


# ============================================================
# CHECK TEST DATA
# ============================================================

test_start = pd.Timestamp(TEST_START)
test_end = pd.Timestamp(TEST_END)

original_test = original_df[
    (original_df["record_date"] >= test_start)
    & (original_df["record_date"] <= test_end)
].copy()

v2_test = v2_df[
    (v2_df["record_date"] >= test_start)
    & (v2_df["record_date"] <= test_end)
].copy()

if len(original_test) != PREDICTION_LENGTH:
    raise ValueError(
        f"Original dataset has {len(original_test)} test rows; "
        f"expected {PREDICTION_LENGTH}."
    )

if len(v2_test) != PREDICTION_LENGTH:
    raise ValueError(
        f"V2 dataset has {len(v2_test)} test rows; "
        f"expected {PREDICTION_LENGTH}."
    )

if not original_test["record_date"].reset_index(drop=True).equals(
    v2_test["record_date"].reset_index(drop=True)
):
    raise ValueError("The two datasets have different test dates.")

if not np.allclose(
    original_test["electricity"].to_numpy(),
    v2_test["electricity"].to_numpy(),
    rtol=1e-5,
    atol=1e-3,
):
    raise ValueError(
        "The actual electricity values differ between datasets."
    )


# ============================================================
# CREATE PREDICTION DATASET
# ============================================================

def create_prediction_dataset(df, unknown_reals):

    train_df = df[
        df["record_date"] <= pd.Timestamp("2025-12-31")
    ].copy()

    if train_df.empty:
        raise ValueError("Training data is empty.")

    training = TimeSeriesDataSet(
        train_df,
        time_idx="time_idx",
        target="electricity",
        group_ids=["series"],
        static_categoricals=["series"],
        time_varying_known_reals=KNOWN_REALS,
        time_varying_unknown_reals=unknown_reals,
        min_encoder_length=ENCODER_LENGTH,
        max_encoder_length=ENCODER_LENGTH,
        min_prediction_length=30,
        max_prediction_length=30,
        add_relative_time_idx=True,
        add_target_scales=True,
        add_encoder_length=True,
    )

    test_start_idx = int(
        df.loc[
            df["record_date"] == test_start,
            "time_idx"
        ].iloc[0]
    )

    test_end_idx = int(
        df.loc[
            df["record_date"] == test_end,
            "time_idx"
        ].iloc[0]
    )

    prediction_data = df[
        (df["time_idx"] >= test_start_idx - ENCODER_LENGTH)
        & (df["time_idx"] <= test_end_idx)
    ].copy()

    prediction_dataset = TimeSeriesDataSet.from_dataset(
        training,
        prediction_data,
        predict=True,
        stop_randomization=True,
        min_prediction_length=PREDICTION_LENGTH,
        max_prediction_length=PREDICTION_LENGTH,
    )

    if len(prediction_dataset) != 1:
        raise ValueError(
            f"Expected one test prediction sequence, "
            f"but found {len(prediction_dataset)}."
        )

    return prediction_dataset


# ============================================================
# LOAD MODEL AND GENERATE PREDICTIONS
# ============================================================

def predict_electricity(model_path, prediction_dataset, model_name):

    if not os.path.exists(model_path):
        raise FileNotFoundError(f"Model not found: {model_path}")

    print(f"\nLoading {model_name}...")

    model = TemporalFusionTransformer.load_from_checkpoint(
        model_path,
        map_location="cpu",
    )

    dataloader = prediction_dataset.to_dataloader(
        train=False,
        batch_size=1,
        num_workers=0,
    )

    predictions = model.predict(
        dataloader,
        mode="prediction",
    )

    if hasattr(predictions, "detach"):
        predictions = predictions.detach().cpu().numpy()

    predictions = np.asarray(predictions)

    if predictions.ndim == 3:
        # Median quantile (0.50)
        prediction_values = predictions[0, :, 3]

    elif predictions.ndim == 2:
        prediction_values = predictions[0]

    else:
        raise ValueError(
            f"Unexpected prediction shape: {predictions.shape}"
        )

    if len(prediction_values) != PREDICTION_LENGTH:
        raise ValueError(
            f"{model_name} returned {len(prediction_values)} "
            f"predictions instead of {PREDICTION_LENGTH}."
        )

    return prediction_values


# ============================================================
# PREPARE BOTH MODELS
# ============================================================

print("Preparing original model dataset...")

original_prediction_dataset = create_prediction_dataset(
    original_df,
    ORIGINAL_UNKNOWN_REALS,
)

print("Preparing Electricity V2 dataset...")

v2_prediction_dataset = create_prediction_dataset(
    v2_df,
    V2_UNKNOWN_REALS,
)


# ============================================================
# GENERATE PREDICTIONS
# ============================================================

original_predictions = predict_electricity(
    ORIGINAL_MODEL_PATH,
    original_prediction_dataset,
    "Original Electricity TFT",
)

v2_predictions = predict_electricity(
    V2_MODEL_PATH,
    v2_prediction_dataset,
    "Electricity V2 TFT",
)


# ============================================================
# COMPARE TEST RESULTS
# ============================================================

actual = original_test["electricity"].to_numpy()

results = pd.DataFrame({
    "record_date": original_test["record_date"].dt.strftime("%Y-%m-%d"),
    "actual_electricity": actual,
    "original_prediction": original_predictions,
    "v2_prediction": v2_predictions,
})

results["original_absolute_error"] = np.abs(
    results["actual_electricity"] - results["original_prediction"]
)

results["v2_absolute_error"] = np.abs(
    results["actual_electricity"] - results["v2_prediction"]
)

results["original_error"] = (
    results["actual_electricity"] - results["original_prediction"]
)

results["v2_error"] = (
    results["actual_electricity"] - results["v2_prediction"]
)


# ============================================================
# CALCULATE METRICS
# ============================================================

def calculate_metrics(actual_values, predicted_values, model_name):

    return {
        "model": model_name,
        "MAE": mean_absolute_error(
            actual_values, predicted_values
        ),
        "RMSE": np.sqrt(
            mean_squared_error(actual_values, predicted_values)
        ),
        "R2": r2_score(
            actual_values, predicted_values
        ),
    }


metrics = pd.DataFrame([
    calculate_metrics(
        actual,
        original_predictions,
        "Original Electricity TFT",
    ),
    calculate_metrics(
        actual,
        v2_predictions,
        "Electricity V2 TFT",
    ),
])

original_mae = metrics.loc[
    metrics["model"] == "Original Electricity TFT", "MAE"
].iloc[0]

v2_mae = metrics.loc[
    metrics["model"] == "Electricity V2 TFT", "MAE"
].iloc[0]

mae_improvement = original_mae - v2_mae

improvement_percentage = (
    (mae_improvement / original_mae) * 100
    if original_mae != 0 else 0
)


# ============================================================
# SAVE RESULTS
# ============================================================

os.makedirs("data", exist_ok=True)

results.to_csv(PREDICTIONS_OUTPUT, index=False)
metrics.to_csv(METRICS_OUTPUT, index=False)


# ============================================================
# DISPLAY RESULTS
# ============================================================

print("\n" + "=" * 65)
print("ELECTRICITY MODEL TEST EVALUATION")
print("=" * 65)

print(f"Test period: {TEST_START} to {TEST_END}")
print(f"Number of test days: {len(actual)}")

print("\nMODEL METRICS")
print(metrics.to_string(index=False))

print("\nMAE COMPARISON")
print(f"Original MAE: {original_mae:.4f}")
print(f"V2 MAE:       {v2_mae:.4f}")
print(f"MAE improvement: {mae_improvement:.4f}")
print(f"Improvement percentage: {improvement_percentage:.2f}%")

print("\nDAILY PREDICTIONS")
print(results.to_string(index=False))

print("\nFILES SAVED")
print(PREDICTIONS_OUTPUT)
print(METRICS_OUTPUT)

print("\nEvaluation completed.")
