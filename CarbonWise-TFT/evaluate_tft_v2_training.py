import pandas as pd
import numpy as np

from pytorch_forecasting import TimeSeriesDataSet
from pytorch_forecasting import TemporalFusionTransformer
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score


# SETTINGS

DATA_PATH = "data/tft_dataset.csv"
MODEL_PATH = "models/tft_model_v2.ckpt"

MAX_ENCODER_LENGTH = 90
MAX_PREDICTION_LENGTH = 30


# LOAD DATA

print("Loading dataset...")

df = pd.read_csv(DATA_PATH)

df["record_date"] = pd.to_datetime(df["record_date"])

df["series"] = "carbon_emissions"

df = df.sort_values("record_date").reset_index(drop=True)

print("Total rows:", len(df))
print("First date:", df["record_date"].min())
print("Last date:", df["record_date"].max())


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
        "food",
        "emission_lag_1",
        "emission_lag_7",
        "emission_lag_14",
        "emission_rolling_7",
        "emission_rolling_30"
    ],

    add_relative_time_idx=True,
    add_target_scales=True,
    add_encoder_length=True,

    allow_missing_timesteps=False,
)


# LOAD TRAINED MODEL

print("\nLoading trained TFT V2 model...")

model = TemporalFusionTransformer.load_from_checkpoint(
    MODEL_PATH
)

model.eval()


# CREATE TRAINING DATALOADER

train_dataloader = training.to_dataloader(
    train=False,
    batch_size=64,
    num_workers=0
)


# GENERATE TRAINING PREDICTIONS

print("\nGenerating training predictions...")

prediction_output = model.predict(
    train_dataloader,
    mode="prediction"
)


# CONVERT PREDICTIONS

predictions = (
    prediction_output
    .detach()
    .cpu()
    .numpy()
)


# GET ACTUAL VALUES

actuals = []

for batch in train_dataloader:

    y = batch[1]

    if isinstance(y, tuple):
        y = y[0]

    actuals.extend(
        y.detach()
        .cpu()
        .numpy()
        .reshape(-1)
    )


# FLATTEN PREDICTIONS

predictions = predictions.reshape(-1)

actuals = np.array(actuals)


# ALIGN LENGTHS

minimum_length = min(
    len(actuals),
    len(predictions)
)

actuals = actuals[:minimum_length]

predictions = predictions[:minimum_length]


# CALCULATE METRICS

training_mae = mean_absolute_error(
    actuals,
    predictions
)

training_mse = mean_squared_error(
    actuals,
    predictions
)

training_rmse = np.sqrt(
    training_mse
)

training_r2 = r2_score(
    actuals,
    predictions
)


# DISPLAY RESULTS

print("\n========================================")
print("TFT V2 TRAINING RESULTS")
print("========================================")

print(
    f"Training samples evaluated: "
    f"{len(actuals)}"
)

print(
    f"MAE:  {training_mae:.2f}"
)

print(
    f"MSE:  {training_mse:.2f}"
)

print(
    f"RMSE: {training_rmse:.2f}"
)

print(
    f"R²:   {training_r2:.4f}"
)