document.addEventListener('DOMContentLoaded', function () {
    const ajoutLieuButton = document.getElementById('ajout_lieu_button');
    const popup = document.getElementById('location-popup');
    const closePopupButton = document.getElementById('close-popup-button');
    const lieuForm = document.getElementById('lieu-form');

    ajoutLieuButton.addEventListener('click', function () {
        popup.style.display = 'block';
    });

    closePopupButton.addEventListener('click', function () {
        popup.style.display = 'none';
    });

    lieuForm.addEventListener('submit', function (event) {
        event.preventDefault();

        // Récupére les données du formulaire
        const formData = new FormData(lieuForm);

        fetch('/ProjetSortie/public/sortie/lieu/creer', {method: 'POST', body: formData,})
            .then(response => {
                if (response.ok) {
                    // Le formulaire a été soumis avec succès
                    popup.style.display = 'none'; // Fermez la pop-up si nécessaire
                } else {
                    // La réponse n'est pas OK, vérifie si elle contient des erreurs JSON
                    response.json()
                        .then(data => {
                            if (data.errors) {
                                //  erreurs JSON ici
                                console.error('Erreurs lors de la soumission du formulaire:', data.errors);
                            } else {
                                console.error('Erreur lors de la soumission du formulaire');
                            }
                        })
                        .catch(error => {
                            console.error('Erreur de parsing JSON:', error);
                        });
                }
            })
            .catch(error => {
                console.error('Erreur lors de la soumission du formulaire:', error);
            });

    });
});
