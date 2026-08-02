const locationInput = document.getElementById("location");
const locationList  = document.getElementById("location-list");

const inputs = document.querySelectorAll(
    "#location, #type, #min_price, #max_price"
);

inputs.forEach(input => {
    input.addEventListener("input", loadData);
});

/* ===== AUTOCOMPLETE ===== */
locationInput.addEventListener("input", function () {
    const value = this.value;

    if (value.length < 1) {
        locationList.innerHTML = "";
        return;
    }

    fetch(`locations.php?term=${encodeURIComponent(value)}`)
        .then(res => res.text())
        .then(data => {
            locationList.innerHTML = data;
        });
});

locationList.addEventListener("click", function (e) {
    if (e.target.classList.contains("autocomplete-item")) {
        locationInput.value = e.target.textContent;
        locationList.innerHTML = "";
        loadData();
    }
});

/* ===== SEARCH ===== */
function loadData() {
    const location = encodeURIComponent(locationInput.value);
    const type     = encodeURIComponent(document.getElementById("type").value);
    const minPrice = encodeURIComponent(document.getElementById("min_price").value);
    const maxPrice = encodeURIComponent(document.getElementById("max_price").value);

    fetch(`search.php?location=${location}&type=${type}&min_price=${minPrice}&max_price=${maxPrice}`)
        .then(res => res.text())
        .then(data => {
            document.getElementById("results").innerHTML = data;
        });
}

/* ===== VIEW PROPERTY DETAILS ===== */
function viewProperty(propertyId) {
    window.location.href = `property-details.php?id=${propertyId}`;
}

loadData();
