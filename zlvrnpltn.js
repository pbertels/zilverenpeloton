function getAjax(url, success) {
    var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    xhr.open('GET', url);
    xhr.onreadystatechange = function() {
        if (xhr.readyState>3 && xhr.status==200) success(xhr.responseText);
    };
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send();
    return xhr;
}
function playVideo(code) {
    if (videoMap[code]) {
        media.src = videoMap[code].url;
        media.play();
    }
}
function invisible() {
    const recent = document.getElementById("recent");
    recent.classList.remove("visible");
}
function stopFlying() {
    const encouragement = document.getElementById("encouragement");
    encouragement.classList.remove("fly");
}
function startFlying() {
    const encouragement = document.getElementById("encouragement");
    encouragement.classList.add("fly");
    window.setTimeout(stopFlying, 5000);
}
function update() {
    const url = domain + "status.php?code=" + wzc + "&caching=" + (new Date().getTime());
    getAjax(url, (data) => {
        var json = JSON.parse(data);
        if (json) {
            if (json.deelnemer.video != status.deelnemer.video) {
                playVideo(json.deelnemer.video);
            }
            status = json;
            status.realisatie = Math.round(status.realisatie * 10) / 10;
            const statusbar = document.getElementById("statusbar");
            let html = "";
            let p1 = Math.ceil( 70 * status.doel / status.max );
            let p2 = Math.ceil( 70 * status.realisatie / status.max );
            let p3 = 70;
            html += "<table border=\"0\"><tr>";
            html += "<td class=\"right\" width=\"14%\">" + wzcFull + "</td>";
            html += "<td class=\"right\" width=\"8%\">0&nbsp;km</td>";
            if (status.realisatie < status.doel) {
                if (status.realisatie > 0) {
                    html += "<td class=\"blue right\" width=\"" + p2 + "%\">" + status.realisatie + "</td>";
                }
                html += "<td class=\"yellow\" width=\"" + (p3-p2) + "%\">" + "&nbsp;" + "</td>";
            }
            else {
                html += "<td class=\"darkblue right\" width=\"" + p1 + "%\"></td>";
                html += "<td class=\"blue right\" width=\"" + (p2-p1) + "%\">" + status.realisatie + "</td>";
                html += "<td class=\"yellow\" width=\"" + (p3-p2) + "%\">" + "&nbsp;" + "</td>";
            }
            html += "<td class=\"left\" width=\"8%\">" + status.max + "&nbsp;km</td>";
            html += "</tr></table>";
            statusbar.innerHTML = html;

            if (status.realisatie > 0) {
                for (let i = 0; i < status.acties.length; i++) {
                    let actie = status.acties[i];
                    const recent = document.getElementById("recent");
                    if (actie.recency < 15 && !recent.classList.contains("visible")) {
                        recent.classList.add("visible");
                        if (actie.type == "individueel") {
                            recent.innerHTML = "<p>" + actie.omschrijving + " <br>fietste " + actie.km + " km erbij!</p>";
                        }
                        else {
                            recent.innerHTML = "<p>Met de '" + actie.omschrijving + "'<br> komen er " + actie.km + " km bij!</p>";
                        }
                        window.setTimeout(invisible, 15000);
                        window.setTimeout(startFlying, 2000);
                    }
                }
            }

        }
        window.setTimeout(update, 5000);
    });
}
