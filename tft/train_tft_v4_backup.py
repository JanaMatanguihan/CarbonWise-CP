
import os
import shutil
import pandas as pd
import lightning.pytorch as pl

from lightning.pytorch.callbacks import (
    EarlyStopping,
    ModelCheckpoint,
    LearningRateMonitor
)

from pytorch_forecasting import (
    TemporalFusionTransformer,
    TimeSeriesDataSet
)

from pytorch_forecasting.metrics import QuantileLoss


# =====================================================
# V4 SETTINGS
# =====================================================

DATA_PATH = "tft/data/tft_dataset.csv"

MODEL_PATH = "tft/models/tft_model_v4.ckpt"

CHECKPOINT_DIR = "tft/models/v4_checkpoints"

MAX_ENCODER_LENGTH = 90
MAX_PREDICTION_LENGTH = 30

BATCH_SIZE = 32
MAX_EPOCHS = 50

LEARNING_RATE = 0.0005
HIDDEN_SIZE = 32
ATTENTION_HEAD_SIZE = 4
DROPOUT = 0.2
HIDDEN_CONTINUOUS_SIZE = 8

SEED = 42


# =====================================================
# REPRODUCIBILITY
# =====================================================

pl.seed_everything(SEED, workers=True)


# =====================================================
# LOAD DATA
# =====================================================

print("Loading CarbonWise dataset...")

df = pd.read_csv(DATA_PATH)

df["record_date"] = pd.to_datetime(df["record_date"])

df = df.sort_values("time_idx").reset_index(drop=True)

df["series"] = "carbon_emissions"

print("Total rows:", len(df))
print("First date:", df["record_date"].min())
print("Last date:", df["record_date"].max())


# =====================================================
# TRAINING DATA
# =====================================================

training_cutoff = df.loc[
    df["dataset"] == "TRAIN",
    "time_idx"
].max()

training_df = df[
    df["time_idx"] <= training_cutoff
].copy()

print("\nTraining cutoff:", training_cutoff)


# =====================================================
# CREATE TFT DATASET
# =====================================================

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


# =====================================================
# VALIDATION DATA
# =====================================================

validation_df = df[
    (df["time_idx"] >=
     training_cutoff - MAX_ENCODER_LENGTH + 1)
    &
    (df["dataset"] != "TEST")
].copy()

validation_start = df.loc[
    df["dataset"] == "VALIDATION",
    "time_idx"
].min()

validation_end = df.loc[
    df["dataset"] == "VALIDATION",
    "time_idx"
].max()

validation_df = validation_df[
    validation_df["time_idx"] <= validation_end
].copy()

validation = TimeSeriesDataSet.from_dataset(
    training,
    validation_df,
    predict=False,
    stop_randomization=True
)


# =====================================================
# DATALOADERS
# =====================================================

train_dataloader = training.to_dataloader(
    train=True,
    batch_size=BATCH_SIZE,
    num_workers=0
)

validation_dataloader = validation.to_dataloader(
    train=False,
    batch_size=BATCH_SIZE,
    num_workers=0
)


# =====================================================
# DISPLAY CONFIGURATION
# =====================================================

print("\n========================================")
print("CARBONWISE TFT V4 CONFIGURATION")
print("========================================")

print("Training samples:", len(training))
print("Validation samples:", len(validation))

print("Encoder length:", MAX_ENCODER_LENGTH)
print("Prediction length:", MAX_PREDICTION_LENGTH)

print("Hidden size:", HIDDEN_SIZE)
print("Attention heads:", ATTENTION_HEAD_SIZE)
print("Dropout:", DROPOUT)
print("Learning rate:", LEARNING_RATE)

print("Batch size:", BATCH_SIZE)
print("Maximum epochs:", MAX_EPOCHS)

print("Training batches:", len(train_dataloader))
print("Validation batches:", len(validation_dataloader))


# =====================================================
# CREATE V4 MODEL
# =====================================================

print("\nCreating TFT V4 model...")

tft_model = TemporalFusionTransformer.from_dataset(
    training,

    learning_rate=LEARNING_RATE,

    hidden_size=HIDDEN_SIZE,

    attention_head_size=ATTENTION_HEAD_SIZE,

    dropout=DROPOUT,

    hidden_continuous_size=HIDDEN_CONTINUOUS_SIZE,

    loss=QuantileLoss(),

    output_size=7,

    log_interval=10,

    reduce_on_plateau_patience=4,
)

print(
    f"Number of parameters: "
    f"{tft_model.size() / 1e3:.1f}k"
)


# =====================================================
# CALLBACKS
# =====================================================

os.makedirs(CHECKPOINT_DIR, exist_ok=True)

checkpoint_callback = ModelCheckpoint(
    dirpath=CHECKPOINT_DIR,

    filename="tft-v4-{epoch:02d}-{val_loss:.4f}",

    monitor="val_loss",

    mode="min",

    save_top_k=1,

    save_last=True
)

early_stopping = EarlyStopping(
    monitor="val_loss",

    min_delta=0.001,

    patience=8,

    mode="min",

    verbose=True
)

lr_monitor = LearningRateMonitor(
    logging_interval="epoch"
)


# =====================================================
# TRAIN V4
# =====================================================

print("\n========================================")
print("STARTING TFT V4 TRAINING")
print("========================================")

trainer = pl.Trainer(
    max_epochs=MAX_EPOCHS,

    accelerator="cpu",

    devices=1,

    gradient_clip_val=0.1,

    enable_model_summary=True,

    callbacks=[
        checkpoint_callback,
        early_stopping,
        lr_monitor
    ]
)

trainer.fit(
    tft_model,

    train_dataloaders=train_dataloader,

    val_dataloaders=validation_dataloader
)


# =====================================================
# SAVE BEST V4 CHECKPOINT
# =====================================================

best_checkpoint = checkpoint_callback.best_model_path

if not best_checkpoint or not os.path.exists(best_checkpoint):
    raise RuntimeError(
        "No best V4 checkpoint was generated. "
        "Check the training and validation logs."
    )

shutil.copy2(
    best_checkpoint,
    MODEL_PATH
)

print("\n========================================")
print("TFT V4 TRAINING COMPLETED")
print("========================================")

print("Best validation loss:", checkpoint_callback.best_model_score)

print("Best checkpoint:", best_checkpoint)

print("Final V4 model saved to:", MODEL_PATH)
