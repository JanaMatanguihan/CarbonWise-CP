import pandas as pd
import numpy as np

from pytorch_forecasting import TimeSeriesDataSet
from pytorch_forecasting import TemporalFusionTransformer
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score


# SETTINGS

DATA_PATH = "tft/data/tft_dataset.csv"
MODEL_PATH = "tft/models/tft_model.ckpt"

MAX_ENCODER_LENGTH = 90
MAX_PREDICTION_LENGTH = 30


# LOAD DATA


print("Loading dataset...")

df = pd.read_csv(DATA_PATH)

df["record_date"] = pd.to_datetime(df["record_date"])

df["series"] = "carbon_emissions"

print("Total rows:", len(df))


# CREATE TRAINING DATASET

training_df = df[
    df["dataset"] == "TRAIN"
].copy()


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

    time_varying_unknown_reals=[
        "total_emission",
        "transportation",
        "electricity",
        "food"
    ],

    add_relative_time_idx=True,
    add_target_scales=True,
    add_encoder_length=True,

    allow_missing_timesteps=False,
)


# VALIDATION DATA

validation_df = df[
    df["dataset"] == "VALIDATION"
].copy()

validation_start = validation_df["time_idx"].min()

validation_data = df[
    df["time_idx"] >= validation_start - MAX_ENCODER_LENGTH
].copy()


validation_dataset = TimeSeriesDataSet.from_dataset(
    training,
    validation_data,
    predict=True,
    stop_randomization=True
)


validation_dataloader = validation_dataset.to_dataloader(
    train=False,
    batch_size=1,
    num_workers=0
)


print("\nValidation samples:", len(validation_dataset))


# LOAD TRAINED MODEL

print("\nLoading trained TFT model...")

model = TemporalFusionTransformer.load_from_checkpoint(
    MODEL_PATH
)

model.eval()

# MAKE 30-DAY VALIDATION PREDICTION

print("\nGenerating 30-day validation prediction...")

prediction_output = model.predict(
    validation_dataloader,
    mode="prediction"
)

predictions = prediction_output.detach().cpu().numpy()

print("Prediction shape:", predictions.shape)


# GET THE FIRST VALIDATION WINDOW


# One prediction window contains 30 days.
predictions = predictions[0]

actual = validation_df[
    "total_emission"
].values[:MAX_PREDICTION_LENGTH]


# CALCULATE METRICS


mae = mean_absolute_error(
    actual,
    predictions
)

rmse = np.sqrt(
    mean_squared_error(
        actual,
        predictions
    )
)

r2 = r2_score(
    actual,
    predictions
)


print("\n========================================")
print("TFT 30-DAY VALIDATION RESULTS")
print("========================================")

print(f"Prediction days: {len(actual)}")
print(f"MAE:  {mae:.2f}")
print(f"RMSE: {rmse:.2f}")
print(f"R²:   {r2:.4f}")


# SAVE RESULTS

results = pd.DataFrame({
    "record_date": validation_df[
        "record_date"
    ].values[:MAX_PREDICTION_LENGTH],

    "actual_emission": actual,

    "predicted_emission": predictions
})


output_path = "tft/data/tft_validation_predictions.csv"

results.to_csv(
    output_path,
    index=False
)


print("\nPredictions saved to:")
print(output_path)

# ============================================================
# PLOT ACTUAL VS PREDICTED
# ============================================================

import matplotlib.pyplot as plt

plt.figure(figsize=(12, 6))

plt.plot(
    results["record_date"],
    results["actual_emission"],
    label="Actual"
)

plt.plot(
    results["record_date"],
    results["predicted_emission"],
    label="TFT Predicted"
)

plt.title("TFT: Actual vs Predicted Carbon Emissions")

plt.xlabel("Date")
plt.ylabel("Total Emission (kg CO₂)")

plt.legend()

plt.xticks(rotation=45)

plt.tight_layout()

plot_path = "tft/data/tft_validation_plot.png"

plt.savefig(plot_path)

plt.close()

print("\nPlot saved to:")
print(plot_path)