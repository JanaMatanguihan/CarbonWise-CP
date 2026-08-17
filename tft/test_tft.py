import pandas as pd
import numpy as np
import matplotlib.pyplot as plt

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

df = df.sort_values(
    "record_date"
).reset_index(drop=True)

print("Total rows:", len(df))

print(
    "First date:",
    df["record_date"].min()
)

print(
    "Last date:",
    df["record_date"].max()
)


# TRAINING DATASET CONFIGURATION


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


# TEST DATA


test_df = df[
    df["dataset"] == "TEST"
].copy()

test_df = test_df.sort_values(
    "record_date"
).reset_index(drop=True)


print("\n========================================")
print("TEST DATA")
print("========================================")

print(
    "First test date:",
    test_df["record_date"].min()
)

print(
    "Last test date:",
    test_df["record_date"].max()
)

print(
    "Test days:",
    len(test_df)
)


# CREATE 30-DAY PREDICTION WINDOW
#
# The trained TFT predicts 30 days.
#
# Our actual TEST period contains only 20 days:
# July 1 → July 20
#
# Therefore we create a 30-day prediction window:
# June 21 → July 20
#
# We will evaluate ONLY:
# July 1 → July 20

test_end_idx = test_df["time_idx"].max()

prediction_start_idx = (
    test_end_idx
    - MAX_PREDICTION_LENGTH
    + 1
)

minimum_time_idx = (
    prediction_start_idx
    - MAX_ENCODER_LENGTH
)


test_data = df[
    (df["time_idx"] >= minimum_time_idx)
    &
    (df["time_idx"] <= test_end_idx)
].copy()

test_data = test_data.sort_values(
    "time_idx"
).reset_index(drop=True)


print("\nPrediction window:")

print(
    "Prediction start:",
    test_data[
        "record_date"
    ].iloc[-MAX_PREDICTION_LENGTH]
)

print(
    "Prediction end:",
    test_data[
        "record_date"
    ].iloc[-1]
)

print(
    "Total rows supplied:",
    len(test_data)
)


# CREATE TEST DATASET

test_dataset = TimeSeriesDataSet.from_dataset(

    training,

    test_data,

    predict=True,

    stop_randomization=True
)


test_dataloader = test_dataset.to_dataloader(

    train=False,

    batch_size=1,

    num_workers=0
)


print(
    "\nTest prediction samples:",
    len(test_dataset)
)


# LOAD TRAINED MODEL

print("\nLoading trained TFT model...")

model = TemporalFusionTransformer.load_from_checkpoint(
    MODEL_PATH
)

model.eval()


# GENERATE PREDICTION

print(
    "\nGenerating 30-day test prediction..."
)

prediction_output = model.predict(

    test_dataloader,

    mode="prediction"
)

predictions = (
    prediction_output
    .detach()
    .cpu()
    .numpy()
)


print(
    "Prediction shape:",
    predictions.shape
)


# First prediction window
predictions = predictions[0]


# GET ACTUAL VALUES FOR THE SAME 30-DAY WINDOW


prediction_actuals = test_data[
    "total_emission"
].values[-MAX_PREDICTION_LENGTH:]


prediction_dates = test_data[
    "record_date"
].values[-MAX_PREDICTION_LENGTH:]


# KEEP ONLY THE ACTUAL TEST PERIOD
#
# July 1 → July 20

test_mask = (
    prediction_dates
    >= test_df[
        "record_date"
    ].min()
)

test_mask = (
    test_mask
    &
    (
        prediction_dates
        <= test_df[
            "record_date"
        ].max()
    )
)


actual = prediction_actuals[
    test_mask
]

predicted = predictions[
    test_mask
]

test_dates = prediction_dates[
    test_mask
]



# METRICS

mae = mean_absolute_error(
    actual,
    predicted
)

rmse = np.sqrt(
    mean_squared_error(
        actual,
        predicted
    )
)

r2 = r2_score(
    actual,
    predicted
)


print("\n========================================")
print("TFT FINAL TEST RESULTS")
print("========================================")

print(
    f"Test days evaluated: {len(actual)}"
)

print(
    f"Test period: "
    f"{pd.Timestamp(test_dates[0]).date()} → "
    f"{pd.Timestamp(test_dates[-1]).date()}"
)

print(
    f"MAE:  {mae:.2f}"
)

print(
    f"RMSE: {rmse:.2f}"
)

print(
    f"R²:   {r2:.4f}"
)


# SAVE TEST PREDICTIONS

results = pd.DataFrame({

    "record_date": test_dates,

    "actual_emission": actual,

    "predicted_emission": predicted
})


output_path = (
    "tft/data/tft_test_predictions.csv"
)


results.to_csv(

    output_path,

    index=False
)


print(
    "\nTest predictions saved to:"
)

print(
    output_path
)


# PLOT

plt.figure(
    figsize=(12, 6)
)


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


plt.title(
    "TFT: Actual vs Predicted Test Carbon Emissions"
)


plt.xlabel(
    "Date"
)


plt.ylabel(
    "Total Emission (kg CO₂)"
)


plt.legend()


plt.xticks(
    rotation=45
)


plt.tight_layout()


plot_path = (
    "tft/data/tft_test_plot.png"
)


plt.savefig(

    plot_path,

    dpi=150
)


plt.close()


print(
    "\nTest plot saved to:"
)

print(
    plot_path
)