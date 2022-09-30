const routes = require('./routes.json');
import Routing from '../../vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js/router.min.js';
Routing.setRoutingData(routes);

import { changeStateElement } from '../js/general';
import { displayPopup } from '../js/general';


/**
 * SUPPRESSION DE LA PHOTO DE PROFIL (EN AJAX).
 * 
 */
const btnDeleteAvatar = document.querySelector('.delete-avatar');

if (btnDeleteAvatar) {
    btnDeleteAvatar.addEventListener('click', (e) => {
    
        const userPicture = document.querySelector('.change-avatar-profil img');
        const avatarByDefault = '/images/avatar/avatar-defaut.jpg';
    
        // On créer l'url de la route à executer avec les paramètre.
        const urlDeleteAvatar = Routing.generate('app_supprimer_avatar');
    
        // Message à afficher si la photo est celle par défaut.
        if (userPicture.getAttribute('src') == avatarByDefault) {
            const message = 'Impossible de supprimer la photo par defaut';
            displayPopup(message, 'notice');
            return false
        }
    
        // On exectue la fonction qui va traiter la requete.
        changeStateElement(e, urlDeleteAvatar)
    });
}



/**
 * VALIDE LA TAILLE DE LA PHOTO;
 * Affiche le nom de la photo séléctionnée par l'utilisateur.
 * Indique si la photo séléctionnée respecte la taille de 2MO.
 * 
 */
const btnSelectAvatar = document.querySelector('.select-avatar');
const detailSelectAvatar = document.querySelector('.detail-select-avatar');

if (btnSelectAvatar) {
    btnSelectAvatar.addEventListener('input', (e) => {
        const namePictureSend = e.target.files[0].name;
        const sizePictureSend = e.target.files[0].size
        const typePictureSend = e.target.files[0].type
        const mimeAccepted = ['image/jpeg', 'image/png', 'image/bmp']
    
        btnSelectAvatar.style.background = '';
    
        if (!mimeAccepted.includes(typePictureSend)) {
            const message = 'Votre photo doit être au format jpeg, png ou bmp';
            displayPopup(message, 'notice', 3000);
        } else if (sizePictureSend > 2000000) {
            const message = 'La photo séléctionnée dépasse les 2Mo';
            displayPopup(message, 'notice', 3000);
        } else {
            detailSelectAvatar.textContent = namePictureSend + ' : Cliquer sur le bouton enregistrer pour confirmer';
            detailSelectAvatar.style.color = '#2668e2';
        }
    })
}