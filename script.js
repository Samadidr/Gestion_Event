document.addEventListener("DOMContentLoaded", function() {
    fetch("get_evenements.php")
    .then(response => response.json())
    .then(events => {
        const eventsContainer = document.getElementById("events-container");

        if (events.length === 0) {
            eventsContainer.innerHTML = "<p>Aucun événement pour le moment.</p>";
        } else {
            events.forEach(event => {
                let eventDiv = document.createElement("div");
                eventDiv.classList.add("event");
                eventDiv.setAttribute("data-aos", "fade-up"); // Animation d'apparition

                eventDiv.innerHTML = `
                    <h3>${event.titre}</h3>
                    <p>${event.description}</p>
                    <p><strong>Date :</strong> ${event.date_event}</p>
                `;

                eventsContainer.appendChild(eventDiv);
            });

            AOS.refresh(); // Mise à jour des animations après ajout dynamique
        }
    })
    .catch(error => console.error("Erreur lors du chargement des événements :", error));
});
