import os

import pandas as pd
import psycopg2
import torch
from dotenv import load_dotenv
from pytorch_forecasting import TemporalFusionTransformer, TimeSeriesDataSet


# Load the Laravel project's environment variables
load_dotenv(".env")


MODEL_PATH = "tft/models/tft_model_v2.ckpt"
TRAINING_DATA_PATH = "tft/data/tft_dataset.csv"
OUTPUT_PATH = "tft/data/tft_v2_forecast.csv"


def get_database_connection():
    return psycopg2.connect(
        host=os.getenv("DB_HOST"),
        port=os.getenv("DB_PORT", "5432"),
        dbname=os.getenv("DB_DATABASE"),
        user=os.getenv("DB_USERNAME"),
        password=os.getenv("DB_PASSWORD"),
        sslmode=os.getenv("DB_SSLMODE", "require"),
        options="endpoint=" + os.getenv("DB_ENDPOINT"),
    )


def get_daily_emissions():
    connection = get_database_connection()

    try:
        query = """
            SELECT
                record_date,
                SUM(total_emission) AS total_emission
            FROM carbon_records
            GROUP BY record_date
            ORDER BY record_date
        """

        df = pd.read_sql_query(query, connection)

        df["record_date"] = pd.to_datetime(df["record_date"])
        df["total_emission"] = pd.to_numeric(df["total_emission"])

        return df

    finally:
        connection.close()


def load_training_data():
    df = pd.read_csv(
        TRAINING_DATA_PATH,
        parse_dates=["record_date"]
    )

    df["series"] = "carbon_emissions"

    return df


def load_tft_model():
    return TemporalFusionTransformer.load_from_checkpoint(
        MODEL_PATH,
        map_location="cpu"
    )


def create_forecast(training_df, model):
    dataset_parameters = model.hparams.dataset_parameters

    forecast_dataset = TimeSeriesDataSet(
        training_df,
        time_idx=dataset_parameters["time_idx"],
        target=dataset_parameters["target"],
        group_ids=dataset_parameters["group_ids"],
        min_encoder_length=dataset_parameters["min_encoder_length"],
        max_encoder_length=dataset_parameters["max_encoder_length"],
        min_prediction_length=dataset_parameters["min_prediction_length"],
        max_prediction_length=dataset_parameters["max_prediction_length"],
        static_categoricals=dataset_parameters["static_categoricals"],
        time_varying_known_reals=dataset_parameters[
            "time_varying_known_reals"
        ],
        time_varying_unknown_reals=dataset_parameters[
            "time_varying_unknown_reals"
        ],
        allow_missing_timesteps=dataset_parameters[
            "allow_missing_timesteps"
        ],
        add_relative_time_idx=dataset_parameters[
            "add_relative_time_idx"
        ],
        add_target_scales=dataset_parameters[
            "add_target_scales"
        ],
        add_encoder_length=dataset_parameters[
            "add_encoder_length"
        ],
        target_normalizer=dataset_parameters[
            "target_normalizer"
        ],
    )

    latest_time_idx = training_df["time_idx"].max()

    history_df = training_df[
        training_df["time_idx"]
        > latest_time_idx - dataset_parameters["max_encoder_length"]
    ].copy()

    prediction_length = dataset_parameters["max_prediction_length"]

    future_dates = pd.date_range(
        start=training_df["record_date"].max()
        + pd.Timedelta(days=1),
        periods=prediction_length,
        freq="D"
    )

    future_df = pd.DataFrame({
        "record_date": future_dates
    })

    future_df["time_idx"] = range(
        int(training_df["time_idx"].max()) + 1,
        int(training_df["time_idx"].max()) + 1
        + prediction_length
    )

    future_df["day_of_week"] = future_df["record_date"].dt.dayofweek
    future_df["month"] = future_df["record_date"].dt.month
    future_df["day_of_month"] = future_df["record_date"].dt.day
    future_df["is_weekend"] = (
        future_df["day_of_week"] >= 5
    ).astype(int)

    future_df["series"] = "carbon_emissions"

    unknown_columns = [
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

    for column in unknown_columns:
        future_df[column] = training_df[column].iloc[-1]

    forecast_input = pd.concat(
        [history_df, future_df],
        ignore_index=True
    )

    prediction_dataset = TimeSeriesDataSet.from_dataset(
        forecast_dataset,
        forecast_input,
        predict=True,
        stop_randomization=True
    )

    prediction_loader = prediction_dataset.to_dataloader(
        train=False,
        batch_size=1,
        num_workers=0
    )

    model.eval()

    with torch.no_grad():
        predictions = model.predict(prediction_loader)

    forecast_values = predictions[0].detach().cpu().numpy()

    return pd.DataFrame({
        "record_date": future_dates,
        "predicted_total_emission": forecast_values
    })


if __name__ == "__main__":
    print("Live TFT forecasting started")

    print("\nLoading live CarbonWise data...")
    live_data = get_daily_emissions()

    print("Live rows:", len(live_data))
    print(
        "Live date range:",
        live_data["record_date"].min().date(),
        "to",
        live_data["record_date"].max().date(),
    )

    print("\nLoading TFT training history...")
    training_data = load_training_data()

    print("Training rows:", len(training_data))
    print(
        "Training date range:",
        training_data["record_date"].min().date(),
        "to",
        training_data["record_date"].max().date(),
    )

    print("\nLoading TFT V2 model...")
    model = load_tft_model()

    print("TFT V2 model loaded successfully")

    print("\nModel configuration:")
    print(
        "Encoder length:",
        model.hparams.dataset_parameters[
            "max_encoder_length"
        ]
    )
    print(
        "Prediction length:",
        model.hparams.dataset_parameters[
            "max_prediction_length"
        ]
    )

    print("\nGenerating forecast...")
    forecast = create_forecast(training_data, model)

    forecast.to_csv(OUTPUT_PATH, index=False)

    print("\nForecast generated successfully")
    print("Forecast saved to:", OUTPUT_PATH)

    print("\nForecast:")
    print(forecast.to_string(index=False))