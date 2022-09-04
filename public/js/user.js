let btnRemoveAvatar = document.querySelector('.remove-avatar');
let btnSelectAvatar = document.querySelector('.select-avatar');
let detailSelectAvatar = document.querySelector('.detail-select-avatar');

/**
 * Demande la confirmation avant de confirmer la suppression de la photo de profil
 */
btnRemoveAvatar.addEventListener('click', (e) => {
    let confirmDelete = confirm('Êtes vous sur de vouloir supprimer votre photo ?');
    if (!confirmDelete) {
        e.preventDefault();  
    }
})

/**
 * Affiche le nom de la photo apres que l'utilisateur l'ai séléctionné
 * Indique si la taille de la photo est correct
 */
btnSelectAvatar.addEventListener('input', (e) => {
    let namePictureSend = e.target.files[0].name;
    let sizePictureSend = e.target.files[0].size
    detailSelectAvatar.style.color = '';
    btnSelectAvatar.style.background = '';

    if (sizePictureSend > 2000000) {
        detailSelectAvatar.textContent = 'Votre photo dépasse les 2Mo';
        detailSelectAvatar.style.color = 'red';
    } else {
        detailSelectAvatar.textContent = 'Vous avez séléctionné : ' + namePictureSend;
        btnSelectAvatar.style.background = '#003f8880';
    }
})