from fastapi import FastAPI
from pydantic import BaseModel
from typing import List

import pandas as pd
import numpy as np
import torch

from pytorch_forecasting import TemporalFusionTransformer
from pytorch_forecasting import TimeSeriesDataSet


# SETTINGS

MODEL_PATH = "models/tft_model_v2.ckpt"

TRAINING_DATA_PATH = "data/tft_dataset.csv"

MAX_ENCODER_LENGTH = 90
MAX_PREDICTION_LENGTH = 30


# FASTAPI

app = FastAPI(
    title="CarbonWise TFT Forecast API"
)


# REQUEST DATA

class CarbonRecord(BaseModel):
    record_date: str
    transportation: float
    electricity: float
    food: float
    total_emission: float


class ForecastRequest(BaseModel):
    records: List[CarbonRecord]


# ROOT ENDPOINT

@app.get("/")
def root():
    return {
        "message": "CarbonWise TFT Forecast API is running"
    }


# FORECAST ENDPOINT

@app.post("/forecast")
def forecast(request: ForecastRequest):

    # CHECK INPUT

    if len(request.records) == 0:
        return {
            "error": "No carbon records were provided."
        }


    # LOAD TRAINING DATA

    training_df = pd.read_csv(
        TRAINING_DATA_PATH
    )

    training_df["record_date"] = pd.to_datetime(
        training_df["record_date"]
    )

    training_df["series"] = "carbon_emissions"


    # GET TRAINING CUTOFF

    training_cutoff = training_df.loc[
        training_df["dataset"] == "TRAIN",
        "time_idx"
    ].max()


    # KEEP TRAINING DATA

    training_history = training_df[
        training_df["time_idx"] <= training_cutoff
    ].copy()

    training_history = training_history.sort_values(
        "record_date"
    ).reset_index(drop=True)


    # CONVERT REQUEST RECORDS

    records = [
        {
            "record_date": record.record_date,
            "transportation": record.transportation,
            "electricity": record.electricity,
            "food": record.food,
            "total_emission": record.total_emission
        }
        for record in request.records
    ]

    request_df = pd.DataFrame(records)

    request_df["record_date"] = pd.to_datetime(
        request_df["record_date"]
    )

    request_df["series"] = "carbon_emissions"


    # REMOVE DUPLICATE REQUEST DATES

    request_df = request_df.drop_duplicates(
        subset=["record_date"],
        keep="last"
    )


    # REMOVE TRAINING RECORDS THAT HAVE THE SAME DATES

    request_dates = set(
        request_df["record_date"]
    )

    training_history = training_history[
        ~training_history["record_date"].isin(
            request_dates
        )
    ].copy()


    # COMBINE TRAINING HISTORY AND REQUEST DATA

    history_df = pd.concat(
        [
            training_history,
            request_df
        ],
        ignore_index=True
    )

    history_df = history_df.sort_values(
        "record_date"
    ).reset_index(drop=True)


    # GET ORIGINAL DATASET START DATE

    original_first_date = (
        training_df["record_date"].min()
    )


    # CREATE A CONTINUOUS DAILY TIMELINE

    last_history_date = (
        history_df["record_date"].max()
    )

    complete_dates = pd.date_range(
        start=original_first_date,
        end=last_history_date,
        freq="D"
    )

    history_df = (
        history_df
        .set_index("record_date")
        .reindex(complete_dates)
        .rename_axis("record_date")
        .reset_index()
    )


    # RESTORE STATIC VALUES

    history_df["series"] = (
        "carbon_emissions"
    )


    # FILL MISSING INPUT VALUES

    history_df["transportation"] = (
        history_df["transportation"]
        .ffill()
        .bfill()
    )

    history_df["electricity"] = (
        history_df["electricity"]
        .ffill()
        .bfill()
    )

    history_df["food"] = (
        history_df["food"]
        .ffill()
        .bfill()
    )


    # FILL MISSING TARGET VALUES

    history_df["total_emission"] = (
        history_df["total_emission"]
        .ffill()
        .bfill()
    )


    # CREATE TIME INDEX

    history_df["time_idx"] = (
        history_df["record_date"]
        - original_first_date
    ).dt.days


    # ADD TIME FEATURES

    history_df["day_of_week"] = (
        history_df["record_date"]
        .dt.dayofweek
    )

    history_df["month"] = (
        history_df["record_date"]
        .dt.month
    )

    history_df["day_of_month"] = (
        history_df["record_date"]
        .dt.day
    )

    history_df["is_weekend"] = (
        history_df["day_of_week"] >= 5
    ).astype(int)


    # ADD LAG FEATURES

    history_df["emission_lag_1"] = (
        history_df["total_emission"]
        .shift(1)
    )

    history_df["emission_lag_7"] = (
        history_df["total_emission"]
        .shift(7)
    )

    history_df["emission_lag_14"] = (
        history_df["total_emission"]
        .shift(14)
    )


    # ADD ROLLING FEATURES

    history_df["emission_rolling_7"] = (
        history_df["total_emission"]
        .rolling(7)
        .mean()
    )

    history_df["emission_rolling_30"] = (
        history_df["total_emission"]
        .rolling(30)
        .mean()
    )


    # FILL INITIAL FEATURE VALUES

    history_df["emission_lag_1"] = (
        history_df["emission_lag_1"]
        .bfill()
    )

    history_df["emission_lag_7"] = (
        history_df["emission_lag_7"]
        .bfill()
    )

    history_df["emission_lag_14"] = (
        history_df["emission_lag_14"]
        .bfill()
    )

    history_df["emission_rolling_7"] = (
        history_df["emission_rolling_7"]
        .bfill()
    )

    history_df["emission_rolling_30"] = (
        history_df["emission_rolling_30"]
        .bfill()
    )


    # CREATE TFT TRAINING DATASET

    training = TimeSeriesDataSet(
        training_history,

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

        allow_missing_timesteps=False
    )


    # LOAD TRAINED TFT MODEL

    model = (
        TemporalFusionTransformer
        .load_from_checkpoint(
            MODEL_PATH
        )
    )

    model.eval()


    # LAST AVAILABLE DATE

    last_date = history_df[
        "record_date"
    ].max()


    # CREATE FUTURE DATES

    future_dates = pd.date_range(
        start=last_date + pd.Timedelta(days=1),
        periods=MAX_PREDICTION_LENGTH,
        freq="D"
    )


    # CREATE FUTURE DATAFRAME

    future_df = pd.DataFrame({
        "record_date": future_dates
    })

    future_df["series"] = (
        "carbon_emissions"
    )


    # CREATE FUTURE TIME INDEX

    future_df["time_idx"] = (
        future_df["record_date"]
        - original_first_date
    ).dt.days


    # ADD FUTURE TIME FEATURES

    future_df["day_of_week"] = (
        future_df["record_date"]
        .dt.dayofweek
    )

    future_df["month"] = (
        future_df["record_date"]
        .dt.month
    )

    future_df["day_of_month"] = (
        future_df["record_date"]
        .dt.day
    )

    future_df["is_weekend"] = (
        future_df["day_of_week"] >= 5
    ).astype(int)


    # USE LAST KNOWN INPUT VALUES

    future_df["transportation"] = (
        history_df[
            "transportation"
        ].iloc[-1]
    )

    future_df["electricity"] = (
        history_df[
            "electricity"
        ].iloc[-1]
    )

    future_df["food"] = (
        history_df[
            "food"
        ].iloc[-1]
    )


    # TEMPORARY TARGET VALUES

    last_emission = (
        history_df[
            "total_emission"
        ].iloc[-1]
    )

    future_df["total_emission"] = (
        last_emission
    )


    # COMBINE HISTORY AND FUTURE

    combined_df = pd.concat(
        [
            history_df,
            future_df
        ],
        ignore_index=True
    )

    combined_df = combined_df.sort_values(
        "record_date"
    ).reset_index(drop=True)


    # RECALCULATE LAG FEATURES

    combined_df["emission_lag_1"] = (
        combined_df["total_emission"]
        .shift(1)
    )

    combined_df["emission_lag_7"] = (
        combined_df["total_emission"]
        .shift(7)
    )

    combined_df["emission_lag_14"] = (
        combined_df["total_emission"]
        .shift(14)
    )


    # RECALCULATE ROLLING FEATURES

    combined_df["emission_rolling_7"] = (
        combined_df["total_emission"]
        .rolling(7)
        .mean()
    )

    combined_df["emission_rolling_30"] = (
        combined_df["total_emission"]
        .rolling(30)
        .mean()
    )


    # FILL MISSING FEATURES

    combined_df[
        "emission_lag_1"
    ] = combined_df[
        "emission_lag_1"
    ].bfill()

    combined_df[
        "emission_lag_7"
    ] = combined_df[
        "emission_lag_7"
    ].bfill()

    combined_df[
        "emission_lag_14"
    ] = combined_df[
        "emission_lag_14"
    ].bfill()

    combined_df[
        "emission_rolling_7"
    ] = combined_df[
        "emission_rolling_7"
    ].bfill()

    combined_df[
        "emission_rolling_30"
    ] = combined_df[
        "emission_rolling_30"
    ].bfill()


    # CREATE PREDICTION DATASET

    prediction_dataset = (
        TimeSeriesDataSet.from_dataset(
            training,
            combined_df,
            predict=True,
            stop_randomization=True,
            allow_missing_timesteps=False
        )
    )


    # CREATE DATALOADER

    prediction_loader = (
        prediction_dataset.to_dataloader(
            train=False,
            batch_size=1,
            num_workers=0
        )
    )


    # GET RAW TFT OUTPUT

    with torch.no_grad():

        predictions = model.predict(
            prediction_loader,
            mode="raw",
            return_x=False
        )


    # GET PREDICTION TENSOR

    prediction_tensor = (
        predictions["prediction"]
    )

    prediction_tensor = (
        prediction_tensor
        .detach()
        .cpu()
        .numpy()
    )


    # CHECK OUTPUT SHAPE

    if prediction_tensor.ndim != 3:

        return {
            "error": "Unexpected TFT prediction shape."
        }


    # GET QUANTILES

    quantiles = list(
        model.loss.quantiles
    )


    # FIND 10TH PERCENTILE

    q10_index = min(
        range(len(quantiles)),
        key=lambda i: abs(
            quantiles[i] - 0.10
        )
    )


    # FIND 50TH PERCENTILE

    q50_index = min(
        range(len(quantiles)),
        key=lambda i: abs(
            quantiles[i] - 0.50
        )
    )


    # FIND 90TH PERCENTILE

    q90_index = min(
        range(len(quantiles)),
        key=lambda i: abs(
            quantiles[i] - 0.90
        )
    )


    # EXTRACT QUANTILES

    q10 = prediction_tensor[
        0,
        :,
        q10_index
    ]

    q50 = prediction_tensor[
        0,
        :,
        q50_index
    ]

    q90 = prediction_tensor[
        0,
        :,
        q90_index
    ]


    # BUILD FORECAST

    forecast = []

    for i in range(
        MAX_PREDICTION_LENGTH
    ):

        forecast.append({

            "record_date":
                future_dates[i]
                .strftime("%Y-%m-%d"),

            "forecast":
                float(q50[i]),

            "lower_bound":
                float(q10[i]),

            "upper_bound":
                float(q90[i])
        })


    # RETURN RESULT

    return {

        "model":
            "Temporal Fusion Transformer",

        "prediction_interval":
            "80%",

        "quantiles":
            quantiles,

        "forecast":
            forecast
    }