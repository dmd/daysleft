#!/usr/bin/python3
import json
import os
import sys
import urllib.request
from datetime import date, datetime
from html import escape
from urllib.parse import parse_qs
from zoneinfo import ZoneInfo

TIMEZONE = ZoneInfo("America/New_York")
LETTER_DAY_URL = "https://3e.org/aps/letter_day_calculator.php"
SLOTS = 4

STYLE = """
        body {
            margin: 0;
            padding: 20px;
            font-family: Arial, sans-serif;
            background-color: white;
            color: black;
            height: 100vh;
            box-sizing: border-box;
        }

        .grid-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 20px;
            height: 100%;
            position: relative;
        }

        .countdown-cell {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 20px;
            box-sizing: border-box;
        }

        .countdown-number {
            font-size: 6rem;
            font-weight: 900;
            line-height: 0.8;
            margin: 0;
            padding: 0;
        }

        .countdown-text {
            font-size: 1.44rem;
            font-weight: 700;
            margin-top: 1rem;
            max-width: 90%;
            word-wrap: break-word;
            line-height: 0.8;
        }

        .date-text {
            font-size: 1.08rem;
            font-weight: 600;
            margin-top: 0.5rem;
            display: inline-block;
        }

        .error {
            font-size: 1rem;
            font-weight: 700;
            color: black;
        }

        .empty {
            visibility: hidden;
        }

        .center-date {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 2rem;
            font-weight: 700;
            color: black;
            z-index: 10;
            pointer-events: none;
            white-space: nowrap;
        }
"""


def countdown_for(params, index, today):
    event = params.get(f"event{index}", [""])[0]
    date_str = params.get(f"date{index}", [""])[0]
    if not event or not date_str:
        return None
    try:
        target = date.fromisoformat(date_str)
    except ValueError:
        return {"number": "!", "text": "Invalid date format", "has_error": True}

    days_left = (target - today).days
    label = escape(event)
    if days_left < 0:
        label += f" was {-days_left} days ago"
    elif days_left == 0:
        label += " is today!"
    formatted_date = f"{target:%B} {target.day}, {target.year}"
    text = f'{label}<br><span class="date-text">{escape(formatted_date)}</span>'
    return {"number": days_left, "text": text, "has_error": False}


def letter_day():
    try:
        with urllib.request.urlopen(LETTER_DAY_URL, timeout=5) as response:
            return json.load(response).get("letterday", "").strip()
    except Exception:
        return ""


def render_cell(countdown):
    if countdown is None:
        return (
            '<div class="countdown-cell empty">'
            '<div class="countdown-number"></div><div class="countdown-text"></div></div>'
        )
    error_class = " error" if countdown["has_error"] else ""
    return (
        '<div class="countdown-cell">'
        f'<div class="countdown-number">{escape(str(countdown["number"]))}</div>'
        f'<div class="countdown-text{error_class}">{countdown["text"]}</div></div>'
    )


def main():
    params = parse_qs(os.environ.get("QUERY_STRING", ""))
    now = datetime.now(TIMEZONE)
    today = now.date()

    countdowns = [c for i in range(1, SLOTS + 1) if (c := countdown_for(params, i, today))]
    countdowns.sort(key=lambda c: (c["has_error"], 0 if c["has_error"] else -c["number"]))
    countdowns += [None] * (SLOTS - len(countdowns))

    center = f"{now:%a %b} {now.day}"
    letter = letter_day()
    if letter:
        center += f" ({escape(letter)})"

    cells = "\n        ".join(render_cell(c) for c in countdowns)
    sys.stdout.write(
        "Content-Type: text/html; charset=utf-8\r\n\r\n"
        "<!DOCTYPE html>\n"
        '<html lang="en">\n<head>\n'
        '    <meta charset="UTF-8">\n'
        '    <meta name="viewport" content="width=device-width, initial-scale=1.0">\n'
        "    <title>Days Left Countdown</title>\n"
        f"    <style>{STYLE}    </style>\n"
        "</head>\n<body>\n"
        '    <div class="grid-container">\n'
        f"        {cells}\n"
        f'        <div class="center-date">{center}</div>\n'
        "    </div>\n</body>\n</html>\n"
    )


main()
