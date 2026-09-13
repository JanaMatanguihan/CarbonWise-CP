import pandas as pd

import lightning.pytorch as pl

from pytorch_forecasting import TemporalFusionTransformer
from pytorch_forecasting.metrics import QuantileLoss

from pytorch_forecasting import TimeSeriesDataSet


# SETTINGS

DATA_PATH = "tft/data/tft_dataset.csv"

# Number of historical days TFT will look at
MAX_ENCODER_LENGTH = 90

# Number of future days TFT will predict
MAX_PREDICTION_LENGTH = 30


# LOAD DATA

print("Loading dataset...")

df = pd.read_csv(DATA_PATH)

df["record_date"] = pd.to_datetime(df["record_date"])

print("Total rows:", len(df))
print("First date:", df["record_date"].min())
print("Last date:", df["record_date"].max())


# ADD TIME SERIES GROUP

# We have one daily carbon-emission time series.
# TFT still requires a group identifier.

df["series"] = "carbon_emissions"


# TRAINING DATA

training_cutoff = df.loc[
    df["dataset"] == "TRAIN",
    "time_idx"
].max()

print("\nTraining cutoff:", training_cutoff)


training_df = df[
    df["time_idx"] <= training_cutoff
].copy()

# CREATE TFT TRAINING DATASET

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

# VALIDATION DATA

# We need some historical records before the validation
# period so TFT has enough encoder history.

validation_start = df.loc[
    df["dataset"] == "VALIDATION",
    "time_idx"
].min()

validation_df = df[
    df["time_idx"] >= validation_start - MAX_ENCODER_LENGTH
].copy()


validation = TimeSeriesDataSet.from_dataset(
    training,
    validation_df,
    predict=False,
    stop_randomization=True
)

# CREATE DATALOADERS

train_dataloader = training.to_dataloader(
    train=True,
    batch_size=64,
    num_workers=0
)

validation_dataloader = validation.to_dataloader(
    train=False,
    batch_size=64,
    num_workers=0
)

# INFORMATION

print("\n========================================")
print("TFT DATASET READY")
print("========================================")

print("Training samples:", len(training))
print("Validation samples:", len(validation))

print("Encoder length:", MAX_ENCODER_LENGTH)
print("Prediction length:", MAX_PREDICTION_LENGTH)

print("\nTraining dataloader batches:",
      len(train_dataloader))

print("Validation dataloader batches:",
      len(validation_dataloader))

print("\nTFT dataset configuration is valid.")

# CREATE TFT MODEL

print("\nCreating Temporal Fusion Transformer model...")

tft_model = TemporalFusionTransformer.from_dataset(
    training,

    learning_rate=0.001,

    hidden_size=16,

    attention_head_size=4,

    dropout=0.1,

    hidden_continuous_size=8,

    loss=QuantileLoss(),

    output_size=7,

    log_interval=10,

    reduce_on_plateau_patience=4,
)


print("\n========================================")
print("TFT MODEL CREATED")
print("========================================")

print(
    f"Number of parameters: "
    f"{tft_model.size() / 1e3:.1f}k"
)

# TRAIN TFT MODEL

print("\nStarting TFT training...")

trainer = pl.Trainer(
    max_epochs=30,
    accelerator="cpu",
    devices=1,
    gradient_clip_val=0.1,
    enable_model_summary=True,
)

trainer.fit(
    tft_model,
    train_dataloaders=train_dataloader,
    val_dataloaders=validation_dataloader,
)

print("\n========================================")
print("TFT TRAINING COMPLETED")
print("========================================")

# SAVE TRAINED MODEL

model_path = "tft/models/tft_model_v2.ckpt"

trainer.save_checkpoint(model_path)

print("\nModel saved to:")
print(model_path)