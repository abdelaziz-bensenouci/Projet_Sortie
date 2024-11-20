// Récupére les éléments du formulaire (etape importante)
const villeField = document.getElementById('sortie_ville');
const lieuField = document.getElementById('sortie_lieu');
const rueField = document.getElementById('sortie_rue');
const latitudeField = document.getElementById('sortie_latitude');
const longitudeField = document.getElementById('sortie_longitude');
const codePostalField = document.getElementById('sortie_codePostal');
//const campusField = document.getElementById('sortie_campus'); // Ajout du champ campus

// Écouteur d'événements pour détecter les changements dans le champ ville
villeField.addEventListener('change', function () {
    const selectedVilleId = villeField.value;

    // Requête AJAX pour obtenir les lieux associés à la ville sélectionnée
    // '/lieux-par-ville' est l'URL AJAX
    fetch(`/ProjetSortie/public/sortie/lieux-par-ville/${selectedVilleId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Réponse HTTP non OK');
            }
            return response.json(); // Parse la réponse JSON
        })
        .then(data => {
            // Mise à jour de la liste déroulante des lieux avec les données reçues
            lieuField.innerHTML = `<option value="">Sélectionner un lieu</option>`;
            data.forEach(lieu => {
                lieuField.innerHTML += `<option value="${lieu.id}">${lieu.nom}</option>`;
            });
        })
        .catch(error => {
            console.error(error);
        });
});

// Écouteur d'événements pour détecter les changements dans le champ lieu
lieuField.addEventListener('change', function () {
    const selectedLieuId = lieuField.value;

    // Requête AJAX pour obtenir les détails du lieu sélectionné
    // '/lieu-details' est l'URL AJAX
    fetch(`/ProjetSortie/public/sortie/lieu-details/${selectedLieuId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Réponse HTTP non OK');
            }
            return response.json(); // Parse la réponse JSON
        })
        .then(data => {
            // Mise à jour des champs avec les données du lieu
            rueField.value = data.rue;
            latitudeField.value = data.latitude;
            longitudeField.value = data.longitude;
            codePostalField.value = data.code_postal;
            campusField.value = data.campus;
        })
        .catch(error => {
            console.error(error);
        });
});


