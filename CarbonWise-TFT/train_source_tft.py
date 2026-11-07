import os

import pandas as pd
import numpy as np
import lightning.pytorch as pl

from pytorch_forecasting import TemporalFusionTransformer
from pytorch_forecasting import TimeSeriesDataSet
from pytorch_forecasting.metrics import QuantileLoss

from sklearn.metrics import (
    mean_absolute_error,
    mean_squared_error,
    r2_score
)


# SETTINGS

DATA_PATH = "data/tft_dataset.csv"

MAX_ENCODER_LENGTH = 90
MAX_PREDICTION_LENGTH = 30

# The TEST set contains only 20 days.
TEST_PREDICTION_LENGTH = 20

SOURCE_COLUMNS = [
    "transportation",
    "electricity",
    "food"
]


# LOAD DATA

print("Loading dataset...")

df = pd.read_csv(DATA_PATH)

df["record_date"] = pd.to_datetime(
    df["record_date"]
)

df["series"] = "carbon_emissions"

df = df.sort_values(
    "record_date"
).reset_index(drop=True)

print("Total rows:", len(df))
print("First date:", df["record_date"].min())
print("Last date:", df["record_date"].max())


# TRAIN EACH EMISSION SOURCE

for target_column in SOURCE_COLUMNS:

    print("\n")
    print("========================================")
    print(
        f"SOURCE MODEL: "
        f"{target_column.upper()}"
    )
    print("========================================")


    # MODEL PATH

    model_path = (
        f"models/tft_model_{target_column}.ckpt"
    )


    # TRAINING DATA

    training_df = df[
        df["dataset"] == "TRAIN"
    ].copy()


    # CREATE TFT TRAINING DATASET

    training = TimeSeriesDataSet(
        training_df,

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


    # REUSE EXISTING MODEL

    if os.path.exists(model_path):

        print("\nExisting model found:")
        print(model_path)

        print(
            "\nReusing the existing trained model..."
        )

    else:

        print("\nNo existing model found.")

        print(
            f"Training new {target_column} "
            "TFT model..."
        )


        # TRAINING DATALOADER

        train_dataloader = training.to_dataloader(
            train=True,
            batch_size=64,
            num_workers=0
        )


        # CREATE MODEL

        print("\nCreating TFT model...")

        tft_model = (
            TemporalFusionTransformer.from_dataset(
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
        )


        # TRAIN MODEL

        print("\nStarting training...")

        trainer = pl.Trainer(
            max_epochs=30,
            accelerator="cpu",
            devices=1,
            gradient_clip_val=0.1,
            enable_model_summary=False,
        )


        trainer.fit(
            tft_model,
            train_dataloaders=train_dataloader
        )


        # SAVE MODEL

        trainer.save_checkpoint(
            model_path
        )

        print("\nModel saved to:")
        print(model_path)


    # LOAD TEST DATA

    test_df = df[
        df["dataset"] == "TEST"
    ].copy()

    test_df = test_df.sort_values(
        "record_date"
    ).reset_index(drop=True)


    print("\nTEST DATA")

    print(
        "Test first date:",
        test_df["record_date"].min()
    )

    print(
        "Test last date:",
        test_df["record_date"].max()
    )

    print(
        "Test days:",
        len(test_df)
    )


    # GET 90 DAYS BEFORE TEST

    test_start_time_idx = test_df[
        "time_idx"
    ].min()

    test_end_time_idx = test_df[
        "time_idx"
    ].max()

    minimum_time_idx = (
        test_start_time_idx
        - MAX_ENCODER_LENGTH
    )


    prediction_data = df[
        (df["time_idx"] >= minimum_time_idx)
        &
        (df["time_idx"] <= test_end_time_idx)
    ].copy()


    # CREATE TEST DATASET

    # The TEST set contains 20 days,
    # so the prediction dataset must also
    # use a 20-day prediction length.

    prediction_dataset = (
        TimeSeriesDataSet.from_dataset(
            training,
            prediction_data,
            predict=True,
            stop_randomization=True,

            min_prediction_length=(
                TEST_PREDICTION_LENGTH
            ),

            max_prediction_length=(
                TEST_PREDICTION_LENGTH
            )
        )
    )


    prediction_dataloader = (
        prediction_dataset.to_dataloader(
            train=False,
            batch_size=1,
            num_workers=0
        )
    )


    # LOAD TRAINED MODEL

    print(
        "\nLoading trained model..."
    )

    model = (
        TemporalFusionTransformer
        .load_from_checkpoint(
            model_path
        )
    )

    model.eval()


    # GENERATE PREDICTIONS

    print(
        "\nGenerating test predictions..."
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


    # ACTUAL TEST VALUES

    actual = test_df[
        target_column
    ].values


    # MATCH LENGTH

    prediction_length = min(
        len(actual),
        len(predictions)
    )

    actual = actual[
        :prediction_length
    ]

    predictions = predictions[
        :prediction_length
    ]


    # CALCULATE METRICS

    mae = mean_absolute_error(
        actual,
        predictions
    )

    mse = mean_squared_error(
        actual,
        predictions
    )

    rmse = np.sqrt(mse)

    r2 = r2_score(
        actual,
        predictions
    )


    # DISPLAY RESULTS

    print("\n========================================")
    print(
        f"{target_column.upper()} TEST RESULTS"
    )
    print("========================================")

    print(
        f"Test days evaluated: "
        f"{len(actual)}"
    )

    print(
        f"MAE:  {mae:.2f}"
    )

    print(
        f"MSE:  {mse:.2f}"
    )

    print(
        f"RMSE: {rmse:.2f}"
    )

    print(
        f"R²:   {r2:.4f}"
    )


# COMPLETED

print("\n")
print("========================================")
print("ALL SOURCE MODELS COMPLETED")
print("========================================")