const btnRemoveAvatar = document.querySelector('.remove-avatar');
const btnSelectAvatar = document.querySelector('.select-avatar');
const detailSelectAvatar = document.querySelector('.detail-select-avatar');

/**
 * Demande la confirmation de l'utilisateur avant de supprimer la photo de profil lorsqu'il clic sur le bouton supprimer de la page profil.
 */
btnRemoveAvatar.addEventListener('click', (e) => {
    let confirmDelete = confirm('Êtes vous sur de vouloir supprimer votre photo ?');
    if (!confirmDelete) {
        e.preventDefault();  
    }
})

/**
 * Affiche le nom de la photo séléctionnée par l'utilisateur.
 * Indique si la photo séléctionnée respecte la taille de 2MO
 */
btnSelectAvatar.addEventListener('input', (e) => {
    let namePictureSend = e.target.files[0].name;
    let sizePictureSend = e.target.files[0].size
    detailSelectAvatar.style.color = '';
    btnSelectAvatar.style.background = '';

    if (sizePictureSend > 2000000) {
        detailSelectAvatar.textContent = 'La photo séléctionnée dépasse les 2Mo';
        detailSelectAvatar.style.color = 'red';
    } else {
        detailSelectAvatar.textContent = 'Vous avez séléctionné : ' + namePictureSend;
        btnSelectAvatar.style.background = '#003f8880';
    }
})