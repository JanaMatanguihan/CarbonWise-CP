import "./bootstrap";

import Alpine from "alpinejs";
window.Alpine = Alpine;
Alpine.start();

import { createIcons } from "lucide";
createIcons();

import flatpickr from "flatpickr";
import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect/index.js";

import "flatpickr/dist/flatpickr.min.css";
import "flatpickr/dist/plugins/monthSelect/style.css";

const picker = document.querySelector("#monthPicker");

if (picker) {
    flatpickr(picker, {
        plugins: [
            monthSelectPlugin({
                shorthand: true,
                dateFormat: "Y-m",
                altFormat: "F Y",
            }),
        ],
        defaultDate: picker.value || "today",
        onChange: function () {
            picker.form.submit();
        },
    });
}