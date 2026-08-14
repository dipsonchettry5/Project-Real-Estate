const locationInput = document.getElementById("location");
const locationList  = document.getElementById("location-list");

const inputs = document.querySelectorAll(
    "#location, #type, #min_price, #max_price"
);

inputs.forEach(input => {
    input.addEventListener("input", loadData);
});

/* ===== AUTOCOMPLETE ===== */
if (locationInput && locationList) {
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
}

/* ===== SEARCH ===== */
function loadData() {
    const resultsElem = document.getElementById("results");
    if (!resultsElem) return;

    const locationVal = locationInput ? locationInput.value : '';
    const typeVal     = document.getElementById("type") ? document.getElementById("type").value : '';
    const minPriceVal = document.getElementById("min_price") ? document.getElementById("min_price").value : '';
    const maxPriceVal = document.getElementById("max_price") ? document.getElementById("max_price").value : '';

    const location = encodeURIComponent(locationVal);
    const type     = encodeURIComponent(typeVal);
    const minPrice = encodeURIComponent(minPriceVal);
    const maxPrice = encodeURIComponent(maxPriceVal);

    fetch(`search.php?location=${location}&type=${type}&min_price=${minPrice}&max_price=${maxPrice}`)
        .then(res => res.text())
        .then(data => {
            resultsElem.innerHTML = data;
        });
}

/* ===== VIEW PROPERTY DETAILS ===== */
function viewProperty(propertyId) {
    window.location.href = `details.php?id=${propertyId}`;
}

/* ===== TOGGLE FAVORITE ===== */
function toggleFavorite(propertyId, btnElement) {
    const formData = new FormData();
    formData.append('property_id', propertyId);

    fetch('toggle_favorite.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            if (btnElement) {
                if (data.is_favorite) {
                    btnElement.classList.add('favorited');
                    btnElement.innerHTML = '❤️';
                    btnElement.setAttribute('title', 'Remove from Favorites');
                } else {
                    btnElement.classList.remove('favorited');
                    btnElement.innerHTML = '🤍';
                    btnElement.setAttribute('title', 'Save to Favorites');

                    const card = btnElement.closest('.card');
                    if (window.location.pathname.includes('favorites.php') && card) {
                        card.remove();
                        if (document.querySelectorAll('.property-grid .card').length === 0) {
                            location.reload();
                        }
                    }
                }
            } else {
                location.reload();
            }
        } else if (data.error === 'not_logged_in') {
            window.location.href = 'login.php';
        }
    })
    .catch(err => console.error(err));
}

if (document.getElementById("results")) {
    loadData();
}
