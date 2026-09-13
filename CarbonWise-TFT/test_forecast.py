import pandas as pd
import requests

# Use the same data the TFT was trained on
df = pd.read_csv("data/tft_dataset.csv")

df["record_date"] = pd.to_datetime(df["record_date"])

# Take the latest 90 days
records = df.tail(90)[
    [
        "record_date",
        "transportation",
        "electricity",
        "food",
        "total_emission",
    ]
].copy()

records["record_date"] = records["record_date"].dt.strftime("%Y-%m-%d")

records = records.to_dict("records")

response = requests.post(
    "http://127.0.0.1:8000/forecast",
    json={"records": records},
    timeout=120
)

print("STATUS:", response.status_code)
print("RESPONSE:")
print(response.text)