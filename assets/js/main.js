// EXPLORE INABANGA - vanilla JavaScript
(function () {
    var CENTER = [9.9833, 124.0667]; // Inabanga, Bohol (approx.)
    var TILES = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    var ATTR = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors';

    // ---- Hamburger menu ----
    var toggle = document.getElementById('navToggle');
    var nav = document.getElementById('mainNav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // ---- Confirm before delete (any element with data-confirm) ----
    var confirmEls = document.querySelectorAll('[data-confirm]');
    for (var i = 0; i < confirmEls.length; i++) {
        confirmEls[i].addEventListener('submit', function (ev) {
            if (!window.confirm(this.getAttribute('data-confirm'))) { ev.preventDefault(); }
        });
    }

    // Everything below needs Leaflet
    if (!window.L) { return; }

    function addTiles(map) {
        L.tileLayer(TILES, { maxZoom: 19, attribution: ATTR }).addTo(map);
    }

    function pinIcon(color) {
        return L.divIcon({
            className: 'pin-wrap',
            html: '<span class="pin" style="background:' + color + '"></span>',
            iconSize: [22, 22],
            iconAnchor: [11, 22],
            popupAnchor: [0, -20]
        });
    }

    // ---- Small map on the destination detail page ----
    var detail = document.getElementById('detailMap');
    if (detail) {
        var lat = parseFloat(detail.getAttribute('data-lat'));
        var lng = parseFloat(detail.getAttribute('data-lng'));
        if (!isNaN(lat) && !isNaN(lng)) {
            var m1 = L.map(detail).setView([lat, lng], 15);
            addTiles(m1);
            L.marker([lat, lng], { icon: pinIcon('#dc2626') }).addTo(m1)
                .bindPopup(document.createTextNode(detail.getAttribute('data-name')).textContent);
        }
    }

    // ---- Full interactive map page ----
    var big = document.getElementById('bigMap');
    if (big && window.MAP_DATA) {
        var map = L.map(big).setView(CENTER, 13);
        addTiles(map);

        var markers = {};
        var group = [];

        function buildPopup(p) {
            var box = document.createElement('div');
            var h = document.createElement('strong');
            h.textContent = p.name;
            var small = document.createElement('div');
            small.textContent = p.label;
            var para = document.createElement('p');
            para.textContent = p.desc;
            var link = document.createElement('a');
            link.href = p.url;
            link.textContent = 'View details';
            box.appendChild(h);
            box.appendChild(small);
            box.appendChild(para);
            box.appendChild(link);
            return box;
        }

        MAP_DATA.forEach(function (p) {
            var color = p.kind === 'destination' ? '#dc2626' : '#2563eb';
            var mk = L.marker([p.lat, p.lng], { icon: pinIcon(color) }).addTo(map);
            mk.bindPopup(buildPopup(p));
            markers[p.key] = mk;
            group.push([p.lat, p.lng]);
        });

        if (group.length > 1) {
            map.fitBounds(group, { padding: [40, 40], maxZoom: 15 });
        }

        function focusPlace(key) {
            var mk = markers[key];
            if (!mk) { return; }
            map.setView(mk.getLatLng(), 16);
            mk.openPopup();
            var items = document.querySelectorAll('#placeList li');
            for (var j = 0; j < items.length; j++) {
                items[j].classList.toggle('active', items[j].getAttribute('data-key') === key);
            }
            if (window.innerWidth <= 820) { big.scrollIntoView({ behavior: 'smooth' }); }
        }

        var listItems = document.querySelectorAll('#placeList li');
        for (var k = 0; k < listItems.length; k++) {
            listItems[k].addEventListener('click', function () {
                focusPlace(this.getAttribute('data-key'));
            });
        }

        // Open a place directly from a link like map.php#d3 or map.php#b2
        if (window.location.hash) {
            focusPlace(window.location.hash.substring(1));
        }
        setTimeout(function () { map.invalidateSize(); }, 200);
    }

    // ---- Admin: click-on-map coordinate picker ----
    var picker = document.getElementById('pickerMap');
    var latInput = document.getElementById('latitude');
    var lngInput = document.getElementById('longitude');
    if (picker && latInput && lngInput) {
        var startLat = parseFloat(latInput.value);
        var startLng = parseFloat(lngInput.value);
        var hasStart = !isNaN(startLat) && !isNaN(startLng);
        var pm = L.map(picker).setView(hasStart ? [startLat, startLng] : CENTER, hasStart ? 16 : 13);
        addTiles(pm);
        var pickMarker = null;
        function placeMarker(la, ln) {
            if (pickMarker) { pickMarker.setLatLng([la, ln]); }
            else { pickMarker = L.marker([la, ln], { icon: pinIcon('#dc2626') }).addTo(pm); }
        }
        if (hasStart) { placeMarker(startLat, startLng); }
        pm.on('click', function (ev) {
            var la = ev.latlng.lat.toFixed(6);
            var ln = ev.latlng.lng.toFixed(6);
            latInput.value = la;
            lngInput.value = ln;
            placeMarker(la, ln);
        });
        setTimeout(function () { pm.invalidateSize(); }, 200);
    }
})();