
import logging
from pathlib import Path
from datetime import date

import numpy as np
import pandas as pd
import torch

from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field
from typing import List

from pytorch_forecasting import (
    TemporalFusionTransformer,
    TimeSeriesDataSet
)


# =====================================================
# CONFIGURATION
# =====================================================

BASE_DIR = Path(__file__).resolve().parent

MODEL_PATH = BASE_DIR / "models" / "tft_model_v6.ckpt"
TRAINING_DATA_PATH = BASE_DIR / "data" / "tft_dataset.csv"

MAX_ENCODER_LENGTH = 90
MAX_PREDICTION_LENGTH = 30

SERIES_NAME = "carbon_emissions"

CATEGORIES = [
    "electricity",
    "transportation",
    "food"
]

CATEGORY_FEATURES = [
    f"{category}_{feature}"
    for category in CATEGORIES
    for feature in [
        "lag_1",
        "lag_7",
        "rolling_7"
    ]
]

ORIGINAL_FEATURES = [
    "emission_lag_1",
    "emission_lag_7",
    "emission_lag_14",
    "emission_rolling_7",
    "emission_rolling_30"
]

UNKNOWN_REALS = [
    "total_emission",
    "transportation",
    "electricity",
    "food",
    *ORIGINAL_FEATURES,
    *CATEGORY_FEATURES
]

KNOWN_REALS = [
    "time_idx",
    "day_of_week",
    "month",
    "day_of_month",
    "is_weekend"
]

NUMERIC_COLUMNS = [
    "transportation",
    "electricity",
    "food",
    "total_emission"
]


# =====================================================
# LOGGING
# =====================================================

logging.basicConfig(level=logging.INFO)

logger = logging.getLogger("CarbonWise-TFT-V6")


# =====================================================
# FASTAPI
# =====================================================

app = FastAPI(
    title="CarbonWise TFT Forecast API",
    description="CarbonWise Temporal Fusion Transformer V6 Forecast API",
    version="6.0.0"
)


# =====================================================
# REQUEST MODELS
# =====================================================

class CarbonRecord(BaseModel):

    record_date: date

    transportation: float = Field(ge=0)

    electricity: float = Field(ge=0)

    food: float = Field(ge=0)

    total_emission: float = Field(ge=0)


class ForecastRequest(BaseModel):

    records: List[CarbonRecord]


# =====================================================
# V6 FEATURE ENGINEERING
# =====================================================

def create_category_features(df):

    df = df.copy()

    df = df.sort_values(
        "time_idx"
    ).reset_index(drop=True)

    for category in CATEGORIES:

        df[f"{category}_lag_1"] = (
            df[category].shift(1)
        )

        df[f"{category}_lag_7"] = (
            df[category].shift(7)
        )

        df[f"{category}_rolling_7"] = (
            df[category]
            .shift(1)
            .rolling(
                window=7,
                min_periods=7
            )
            .mean()
        )

    return df


# =====================================================
# REBUILD ALL HISTORICAL FEATURES
# =====================================================

def rebuild_historical_features(df):

    df = df.copy()

    df = df.sort_values(
        "record_date"
    ).reset_index(drop=True)

    df["time_idx"] = (
        df["record_date"] - ORIGINAL_FIRST_DATE
    ).dt.days

    df["series"] = SERIES_NAME

    # Calendar features

    df["day_of_week"] = (
        df["record_date"].dt.dayofweek
    )

    df["month"] = (
        df["record_date"].dt.month
    )

    df["day_of_month"] = (
        df["record_date"].dt.day
    )

    df["is_weekend"] = (
        df["day_of_week"] >= 5
    ).astype(int)

    # Original emission lag features

    df["emission_lag_1"] = (
        df["total_emission"].shift(1)
    )

    df["emission_lag_7"] = (
        df["total_emission"].shift(7)
    )

    df["emission_lag_14"] = (
        df["total_emission"].shift(14)
    )

    # Original rolling features
    # Shift first to exclude the current day.

    df["emission_rolling_7"] = (
        df["total_emission"]
        .shift(1)
        .rolling(window=7)
        .mean()
    )

    df["emission_rolling_30"] = (
        df["total_emission"]
        .shift(1)
        .rolling(window=30)
        .mean()
    )

    # V6 category-specific features

    df = create_category_features(df)

    return df


# =====================================================
# LOAD TRAINING DATA
# =====================================================

if not TRAINING_DATA_PATH.exists():
    raise FileNotFoundError(
        f"Training dataset not found: {TRAINING_DATA_PATH}"
    )

if not MODEL_PATH.exists():
    raise FileNotFoundError(
        f"V6 model not found: {MODEL_PATH}"
    )


logger.info("Loading V6 training dataset...")

source_df = pd.read_csv(
    TRAINING_DATA_PATH
)

source_df["record_date"] = pd.to_datetime(
    source_df["record_date"]
)

source_df = source_df.sort_values(
    "time_idx"
).reset_index(drop=True)

source_df["series"] = SERIES_NAME

ORIGINAL_FIRST_DATE = source_df[
    "record_date"
].min()


# =====================================================
# RECREATE V6 TRAINING FEATURES
# =====================================================

logger.info("Recreating V6 training features...")

training_features_df = create_category_features(
    source_df
)

training_features_df = training_features_df.dropna(
    subset=CATEGORY_FEATURES
).reset_index(drop=True)


# =====================================================
# RECREATE ORIGINAL V6 TRAINING SPLIT
# =====================================================

training_cutoff = training_features_df.loc[
    training_features_df["dataset"] == "TRAIN",
    "time_idx"
].max()

training_df = training_features_df[
    training_features_df["time_idx"] <= training_cutoff
].copy()


# =====================================================
# RECREATE V6 TIMESERIES DATASET
# =====================================================

logger.info("Creating V6 TimeSeriesDataSet...")

training_dataset = TimeSeriesDataSet(

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

    time_varying_known_reals=KNOWN_REALS,

    time_varying_unknown_reals=UNKNOWN_REALS,

    add_relative_time_idx=True,

    add_target_scales=True,

    add_encoder_length=True,

    allow_missing_timesteps=False
)


# =====================================================
# LOAD V6 CHECKPOINT
# =====================================================

logger.info("Loading V6 checkpoint...")

model = TemporalFusionTransformer.load_from_checkpoint(
    str(MODEL_PATH),
    map_location="cpu"
)

model.eval()

logger.info("V6 checkpoint loaded successfully.")

logger.info(
    "Training cutoff: %s",
    training_cutoff
)

logger.info(
    "Original first date: %s",
    ORIGINAL_FIRST_DATE
)


# =====================================================
# ROOT ENDPOINT
# =====================================================

@app.get("/")
def root():

    return {
        "message": "CarbonWise TFT Forecast API is running",
        "model": "Temporal Fusion Transformer V6",
        "status": "ready",
        "encoder_length": MAX_ENCODER_LENGTH,
        "prediction_length": MAX_PREDICTION_LENGTH
    }


# =====================================================
# HEALTH CHECK
# =====================================================

@app.get("/health")
def health():

    return {
        "status": "healthy",
        "model": "V6",
        "model_loaded": model is not None
    }


# =====================================================
# PREPARE HISTORICAL INPUT
# =====================================================

def prepare_history(records):

    # Convert request records to DataFrame.

    request_df = pd.DataFrame([
        {
            "record_date": pd.Timestamp(
                record.record_date
            ),
            "transportation": record.transportation,
            "electricity": record.electricity,
            "food": record.food,
            "total_emission": record.total_emission
        }
        for record in records
    ])

    request_df["record_date"] = pd.to_datetime(
        request_df["record_date"]
    )

    # Remove dates before the original training timeline.

    request_df = request_df[
        request_df["record_date"] >= ORIGINAL_FIRST_DATE
    ].copy()

    if request_df.empty:
        raise ValueError(
            "No records are within the V6 training date range."
        )

    # Use original TRAIN observations as historical baseline.
    # Incoming request records take priority for duplicate dates.

    baseline_df = source_df[
        source_df["dataset"] == "TRAIN"
    ][
        [
            "record_date",
            *NUMERIC_COLUMNS
        ]
    ].copy()

    combined = pd.concat(
        [
            baseline_df,
            request_df
        ],
        ignore_index=True
    )

    combined = (
        combined
        .sort_values("record_date")
        .drop_duplicates(
            subset=["record_date"],
            keep="last"
        )
        .reset_index(drop=True)
    )

    last_date = combined["record_date"].max()

    # Create a continuous daily timeline.

    complete_dates = pd.date_range(
        start=ORIGINAL_FIRST_DATE,
        end=last_date,
        freq="D"
    )

    combined = (
        combined
        .set_index("record_date")
        .reindex(complete_dates)
        .rename_axis("record_date")
        .reset_index()
    )

    # Forward-fill missing observations.

    for column in NUMERIC_COLUMNS:

        combined[column] = (
            pd.to_numeric(
                combined[column],
                errors="coerce"
            )
            .ffill()
        )

    if combined[NUMERIC_COLUMNS].isnull().any().any():

        raise ValueError(
            "Historical data contains missing values "
            "that cannot be forward-filled."
        )

    return combined


# =====================================================
# FORECAST ENDPOINT
# =====================================================

@app.post("/forecast")
def forecast(request: ForecastRequest):

    if not request.records:

        raise HTTPException(
            status_code=400,
            detail="No carbon records were provided."
        )

    try:

        logger.info(
            "Received %s historical records.",
            len(request.records)
        )

        # ---------------------------------------------
        # PREPARE HISTORY
        # ---------------------------------------------

        history_base = prepare_history(
            request.records
        )

        last_date = history_base[
            "record_date"
        ].max()

        # ---------------------------------------------
        # CREATE 30 FUTURE DATES
        # ---------------------------------------------

        future_dates = pd.date_range(
            start=last_date + pd.Timedelta(days=1),
            periods=MAX_PREDICTION_LENGTH,
            freq="D"
        )

        # Unknown future values are placeholders.
        # V6 decoder configuration does not use unknown
        # future variables as prediction inputs.

        last_values = {
            column: float(history_base[column].iloc[-1])
            for column in NUMERIC_COLUMNS
        }

        future_df = pd.DataFrame({
            "record_date": future_dates
        })

        for column, value in last_values.items():

            future_df[column] = value

        # ---------------------------------------------
        # COMBINE HISTORY AND FUTURE
        # ---------------------------------------------

        combined_base = pd.concat(
            [
                history_base,
                future_df
            ],
            ignore_index=True
        )

        # ---------------------------------------------
        # RECREATE ALL V6 HISTORICAL FEATURES
        # ---------------------------------------------

        combined_features = rebuild_historical_features(
            combined_base
        )

        combined_features = combined_features.dropna(
            subset=ORIGINAL_FEATURES + CATEGORY_FEATURES
        ).reset_index(drop=True)

        # ---------------------------------------------
        # KEEP 90 HISTORICAL + 30 FUTURE ROWS
        # ---------------------------------------------

        expected_rows = (
            MAX_ENCODER_LENGTH + MAX_PREDICTION_LENGTH
        )

        prediction_df = combined_features.tail(
            expected_rows
        ).copy()

        if len(prediction_df) != expected_rows:

            raise ValueError(
                "Insufficient valid historical data. "
                f"Required {expected_rows} rows; "
                f"available {len(prediction_df)}."
            )

        # ---------------------------------------------
        # CREATE PREDICTION DATASET
        # ---------------------------------------------

        prediction_dataset = TimeSeriesDataSet.from_dataset(
            training_dataset,
            prediction_df,
            predict=True,
            stop_randomization=True
        )

        if len(prediction_dataset) == 0:

            raise ValueError(
                "V6 could not create a valid prediction window."
            )

        prediction_loader = prediction_dataset.to_dataloader(
            train=False,
            batch_size=1,
            num_workers=0
        )

        # ---------------------------------------------
        # GENERATE RAW QUANTILE PREDICTIONS
        # ---------------------------------------------

        with torch.no_grad():

            predictions = model.predict(
                prediction_loader,
                mode="raw",
                return_x=False
            )

        prediction_tensor = (
            predictions["prediction"]
            .detach()
            .cpu()
            .numpy()
        )

        if prediction_tensor.ndim != 3:

            raise ValueError(
                "Unexpected V6 prediction tensor shape."
            )

        # ---------------------------------------------
        # EXTRACT QUANTILES
        # ---------------------------------------------

        quantiles = list(
            model.loss.quantiles
        )

        def get_quantile_index(target):

            return min(
                range(len(quantiles)),
                key=lambda i: abs(
                    quantiles[i] - target
                )
            )

        q10_index = get_quantile_index(0.10)

        q50_index = get_quantile_index(0.50)

        q90_index = get_quantile_index(0.90)

        q10 = prediction_tensor[
            0, :, q10_index
        ]

        q50 = prediction_tensor[
            0, :, q50_index
        ]

        q90 = prediction_tensor[
            0, :, q90_index
        ]

        # ---------------------------------------------
        # BUILD FORECAST RESPONSE
        # ---------------------------------------------

        forecast_results = []

        for i in range(MAX_PREDICTION_LENGTH):

            forecast_results.append({

                "record_date": future_dates[
                    i
                ].strftime("%Y-%m-%d"),

                "forecast": float(q50[i]),

                "lower_bound": float(q10[i]),

                "upper_bound": float(q90[i])

            })

        logger.info(
            "V6 forecast generated successfully."
        )

        return {

            "model": "Temporal Fusion Transformer V6",

            "prediction_interval": "80%",

            "quantiles": quantiles,

            "forecast": forecast_results

        }

    except HTTPException:
        raise

    except Exception as e:

        logger.exception(
            "V6 forecasting failed."
        )

        raise HTTPException(
            status_code=500,
            detail=f"V6 forecasting failed: {str(e)}"
        )
