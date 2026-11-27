import pandas as pd
import numpy as np

from pytorch_forecasting import (
    TemporalFusionTransformer,
    TimeSeriesDataSet
)

from sklearn.metrics import (
    mean_absolute_error,
    mean_squared_error,
    r2_score
)


DATA_PATH = "data/tft_dataset.csv"

MAX_ENCODER_LENGTH = 90
TEST_PREDICTION_LENGTH = 20

SOURCE_MODELS = {
    "Transportation": (
        "transportation",
        "models/tft_model_transportation.ckpt"
    ),
    "Office Resource (Electricity)": (
        "electricity",
        "models/tft_model_electricity.ckpt"
    ),
    "Food Consumption": (
        "food",
        "models/tft_model_food.ckpt"
    )
}


def evaluate_source(
    source_name,
    target_column,
    model_path
):

    print("\n")
    print("=" * 60)
    print(f"{source_name.upper()} TEST RESULTS")
    print("=" * 60)

    # LOAD DATA

    df = pd.read_csv(DATA_PATH)

    df["record_date"] = pd.to_datetime(
        df["record_date"]
    )

    df["series"] = "carbon_emissions"

    df = df.sort_values(
        "record_date"
    ).reset_index(drop=True)


    # TRAINING DATA

    training_df = df[
        df["dataset"] == "TRAIN"
    ].copy()


    # TEST DATA

    test_df = df[
        df["dataset"] == "TEST"
    ].copy()

    test_df = test_df.sort_values(
        "record_date"
    ).reset_index(drop=True)


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


    # CREATE THE EXACT SAME DATASET
    # CONFIGURATION USED DURING TRAINING

    training = TimeSeriesDataSet(

        training_df,

        time_idx="time_idx",

        target=target_column,

        group_ids=["series"],

        min_encoder_length=MAX_ENCODER_LENGTH,

        max_encoder_length=MAX_ENCODER_LENGTH,

        min_prediction_length=30,

        max_prediction_length=30,

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

        allow_missing_timesteps=False
    )


    # GET THE 90 DAYS BEFORE THE TEST PERIOD

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
        (
            df["time_idx"]
            >= minimum_time_idx
        )
        &
        (
            df["time_idx"]
            <= test_end_time_idx
        )
    ].copy()


    prediction_data = prediction_data.sort_values(
        "time_idx"
    ).reset_index(drop=True)


    print(
        "Prediction history + test rows:",
        len(prediction_data)
    )


    # CREATE TEST DATASET

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


    # LOAD EXISTING MODEL

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
        "Generating test predictions..."
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


    # FIRST / ONLY TEST WINDOW

    predictions = predictions[0]


    # ACTUAL VALUES

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


    # METRICS

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


    # RESULTS

    print("\n========================================")

    print(
        f"{source_name.upper()} TEST RESULTS"
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


    return {

        "Emission Source":
            source_name,

        "MAE":
            mae,

        "MSE":
            mse,

        "RMSE":
            rmse,

        "R²":
            r2

    }


# RUN ALL THREE MODELS

results = []


for source_name, (
    target_column,
    model_path
) in SOURCE_MODELS.items():

    result = evaluate_source(

        source_name,

        target_column,

        model_path

    )

    results.append(result)


# TABLE 15

print("\n\n")

print("=" * 70)

print(
    "TABLE 15 — SOURCE-LEVEL TEST RESULTS"
)

print("=" * 70)


results_df = pd.DataFrame(
    results
)


print(
    results_df.to_string(
        index=False,

        formatters={

            "MAE":
                "{:.2f}".format,

            "MSE":
                "{:.2f}".format,

            "RMSE":
                "{:.2f}".format,

            "R²":
                "{:.4f}".format

        }
    )
)


print("\nEvaluation completed.")