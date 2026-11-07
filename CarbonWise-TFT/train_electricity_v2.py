
import os
import numpy as np
import pandas as pd
import lightning.pytorch as pl

from lightning.pytorch.callbacks import EarlyStopping, ModelCheckpoint

from pytorch_forecasting import (
    TemporalFusionTransformer,
    TimeSeriesDataSet,
)
from pytorch_forecasting.metrics import QuantileLoss


# ============================================================
# SETTINGS
# ============================================================

DATA_PATH = "data/tft_dataset_electricity_v2.csv"
MODEL_PATH = "models/tft_model_electricity_v2.ckpt"

MAX_ENCODER_LENGTH = 90
MAX_PREDICTION_LENGTH = 30

BATCH_SIZE = 64
MAX_EPOCHS = 30
LEARNING_RATE = 0.001

TARGET = "electricity"

ELECTRICITY_FEATURES = [
    "electricity_lag_1",
    "electricity_lag_7",
    "electricity_lag_14",
    "electricity_rolling_7",
    "electricity_rolling_30",
]


# ============================================================
# LOAD DATA
# ============================================================

print("\nLoading electricity dataset...")

df = pd.read_csv(DATA_PATH)

df["record_date"] = pd.to_datetime(df["record_date"])
df["time_idx"] = df["time_idx"].astype(int)
df["series"] = "electricity"

df = df.sort_values("time_idx").reset_index(drop=True)

train_df = df[df["dataset"] == "TRAIN"].copy()
validation_df = df[df["dataset"] == "VALIDATION"].copy()

print("Total rows:", len(df))
print("Training rows:", len(train_df))
print("Validation rows:", len(validation_df))

print("Training period:",
      train_df["record_date"].min(),
      "to",
      train_df["record_date"].max())

print("Validation period:",
      validation_df["record_date"].min(),
      "to",
      validation_df["record_date"].max())


# ============================================================
# CREATE TRAINING DATASET
# ============================================================

print("\nCreating training dataset...")

training = TimeSeriesDataSet(
    train_df,

    time_idx="time_idx",
    target=TARGET,
    group_ids=["series"],

    min_encoder_length=MAX_ENCODER_LENGTH,
    max_encoder_length=MAX_ENCODER_LENGTH,

    min_prediction_length=MAX_PREDICTION_LENGTH,
    max_prediction_length=MAX_PREDICTION_LENGTH,

    static_categoricals=[
        "series",
    ],

    time_varying_known_reals=[
        "time_idx",
        "day_of_week",
        "month",
        "day_of_month",
        "is_weekend",
    ],

    time_varying_unknown_reals=[
        TARGET,
        *ELECTRICITY_FEATURES,
    ],

    add_relative_time_idx=True,
    add_target_scales=True,
    add_encoder_length=True,

    allow_missing_timesteps=False,
)


# ============================================================
# CREATE VALIDATION DATASET
# ============================================================

print("\nCreating validation dataset...")

validation = TimeSeriesDataSet.from_dataset(
    training,
    df[
        df["dataset"].isin(["TRAIN", "VALIDATION"])
    ].copy(),

    min_prediction_idx=int(validation_df["time_idx"].min()),

    predict=False,
    stop_randomization=True,
)

print("Validation samples:", len(validation))


# ============================================================
# DATALOADERS
# ============================================================

print("\nCreating dataloaders...")

train_dataloader = training.to_dataloader(
    train=True,
    batch_size=BATCH_SIZE,
    num_workers=0,
)

validation_dataloader = validation.to_dataloader(
    train=False,
    batch_size=BATCH_SIZE,
    num_workers=0,
)


# ============================================================
# CREATE TFT MODEL
# ============================================================

print("\nCreating Electricity TFT model...")

tft = TemporalFusionTransformer.from_dataset(
    training,

    learning_rate=LEARNING_RATE,

    hidden_size=16,
    attention_head_size=4,

    dropout=0.1,
    hidden_continuous_size=8,

    loss=QuantileLoss(),
    output_size=7,

    log_interval=10,
    reduce_on_plateau_patience=4,
)

print(
    "Model parameters:",
    sum(p.numel() for p in tft.parameters())
)


# ============================================================
# CALLBACKS
# ============================================================

os.makedirs("models", exist_ok=True)

checkpoint_callback = ModelCheckpoint(
    dirpath="models",
    filename="electricity_v2_best",
    monitor="val_loss",
    mode="min",
    save_top_k=1,
)

early_stopping = EarlyStopping(
    monitor="val_loss",
    min_delta=0.001,
    patience=5,
    verbose=True,
    mode="min",
)


# ============================================================
# TRAINING
# ============================================================

print("\n========================================")
print("TRAINING ELECTRICITY TFT V2")
print("========================================")

trainer = pl.Trainer(
    max_epochs=MAX_EPOCHS,

    accelerator="cpu",
    devices=1,

    gradient_clip_val=0.1,

    callbacks=[
        checkpoint_callback,
        early_stopping,
    ],

    enable_model_summary=False,
    enable_progress_bar=True,
    log_every_n_steps=10,
)

trainer.fit(
    tft,
    train_dataloaders=train_dataloader,
    val_dataloaders=validation_dataloader,
)


# ============================================================
# SAVE BEST CHECKPOINT
# ============================================================

best_checkpoint = checkpoint_callback.best_model_path

if not best_checkpoint or not os.path.exists(best_checkpoint):
    raise FileNotFoundError(
        "Best electricity checkpoint was not generated."
    )

print("\nBest checkpoint:", best_checkpoint)

best_model = TemporalFusionTransformer.load_from_checkpoint(
    best_checkpoint,
    map_location="cpu",
)

best_model.eval()

trainer.save_checkpoint(MODEL_PATH)

print("\n========================================")
print("ELECTRICITY TFT V2 TRAINING COMPLETED")
print("========================================")

print("Best validation loss:", checkpoint_callback.best_model_score)
print("Final checkpoint:", MODEL_PATH)
print("Checkpoint exists:", os.path.exists(MODEL_PATH))
