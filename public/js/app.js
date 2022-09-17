/**
 * JS COMMAIN À TOUTES LES PAGES.
 * - Menu mobile.
 * - Info bulle sur icone menu.
 * - Surbrillance du menu.
 * - Restreint l'écriture au non admin.
 */



/**
 * MENU MOBILE.
 * Au clic sur l'icone d'hamburger, on affiche le menu de navigation.
 */
 const iconHbrgMenu = document.querySelector('.hamburger-menu');
 const displayMenuMobile = document.querySelector('.display-menu-mobile');
 const iconCloseMenu = document.querySelector('.close-menu');
 const menuNavigation = document.querySelector('.menu-navigation');
 const overlayMenuMobile = document.createElement('div');
 
 iconHbrgMenu.addEventListener('click', () => {
     // On supprime la classe qui cache le menu
     displayMenuMobile.classList.remove('display-menu-mobile');
     
     // On ajoute un backgroud sombre pour masquer légérement la page.
     overlayMenuMobile.classList.add('menu-mobile-overlay');
     menuNavigation.before(overlayMenuMobile);
     
     // Au clic sur l'icon de fermeture, on lance la fonction qui ferme le menu.
     iconCloseMenu.addEventListener('click',afterMenuClose)
 
     // Au clic sur le overlay, on lance la fonction qui ferme le menu.
     overlayMenuMobile.addEventListener('click', afterMenuClose)
 })
 
 /**
  * PERMET DE FERMER LE MENU ET SUPPRIMER L'OVERLAY.
  */
 function afterMenuClose() {
     displayMenuMobile.classList.add('display-menu-mobile');
     overlayMenuMobile.remove();
 }
 
 
 
 /**
  * INFO BULLE.
  * Au passage de la souris sur une icone du menu, on affiche le nom du menu.
  * Uniquement pour le format compris entre 620 et 970px de large.
  */
 const liMenu = document.querySelectorAll('.menu-navigation ul > a, .logout-mobile a');
 const spanMenu = document.createElement('span');
 
 liMenu.forEach(li => {
     li.addEventListener('mouseenter', () => {
         if (window.innerWidth > 620 && window.innerWidth < 970) {
             li.prepend(spanMenu);
             spanMenu.classList.add('bulle-menu');
             spanMenu.innerHTML = li.textContent
 
             // On supprile l'info bulle lorsque la souris n'est plus sur l'icone.
             li.addEventListener('mouseleave', () => {
                 spanMenu.innerText = '';
                 spanMenu.remove();
             })
         }
     });
 });
 
 
 
 /**
  * SURBRILLANCE MENU.
  * Met en surbrillance un lien du menu si on est sur l'url de ce lien.
  */
 const currentURI = window.location.pathname;
 //const liMenu = document.querySelectorAll('.menu-navigation ul > a, .logout-mobile a');
 
 window.addEventListener('load', () => {
     liMenu.forEach(li => {
         let linkMenu = li.getAttribute('href');
 
         if (linkMenu == currentURI) {
             li.style.color = '#FFF';
             li.classList.add('active-menu');
         }
     });
 })
 
 
 
 /**
  * RESTREINT L'ÉCRITURE AU NON ADMIN.
  * On récupère toutes les balises qui ont une classes no-access.
  * L'attribut data hasAccess renvoi true si l'utilisateur est un admin.
  * On affiche une popup si l'utilisateur clique sur un élément auquel il n'a pas acces.
  */
 const noAccess = document.querySelectorAll('.no-access');
 const userRole = document.querySelector('.js-user-access');
 const hasAccess = userRole.dataset.hasAccess;
 
 for (let i = 0; i < noAccess.length; i++) {
     noAccess[i].addEventListener('click', (e) => {
 
         if (hasAccess === 'false') {
             messagePopup = 'Vous n\'êtes pas autorisé à effectuer cette action.';
             displayPopup(messagePopup, 'notice');
 
             e.preventDefault();
         }
     })
 }
 
 
 
 /**
  * AFFICHGAGE D'UNE POPUP .
  * @param {Message à afficher dans la popup} message 
  * @param {success, notice} typePopup 
  * 
  */
  function displayPopup(message, typePopup = 'success') {
     const popup = document.createElement('div');
     popup.innerText = message;
 
     if(typePopup === 'success') {
         popup.classList.add('pop-up', 'pop-up-success');
     } else if(typePopup === 'notice') {
         popup.classList.add('pop-up', 'pop-up-notice');
     }
 
     document.body.prepend(popup);
     setTimeout(() => {
         popup.remove();
     }, 8000)
 }
 
 
 
 /**
  * TRAITEMENT D'UNE REQUETE AJAX SIMPLE.
  * Affiche un loader le temps du traitement.
  * Affiche une popup de success avec un message.
  * 
  */
  function inFctOnReadyStateChange() {
     const loader = document.createElement('div');
     xhr.onreadystatechange = function () {
         resultat = xhr.response;
         // On affiche un loader le temps du traitement de la requete
         if (this.readyState == 3) {
 
             loader.classList.add('loader');
             document.body.prepend(loader);
         } else if (this.readyState == 4 && this.status == 200) {
             // Si la requete c'est bien passée, on affiche un message de succès.
             let successMessage = 'La modification a été apportée avec succès.'
 
             // On simule un temps de traitement de 2sec.
             setTimeout(() => {
                 // On supprime le loader et on affiche une popup de succes.
                 loader.remove();
                 displayPopup(successMessage);
             }, 2000)
         } else if (this.readyState == 4) {
             return false;
         }
     };
 }
 
 
 
 /**
  * CHANGEMENT D'ÉTAT D'UNE FRANCHISE (EN AJAX).
  * 
  */
 // On récupère tous les inputs contenu dans un élément avec l'id state-franchise.
 const inputState = document.querySelectorAll('#state-franchise > input[type=checkbox]');
 
 inputState.forEach(input => {
     input.addEventListener('click', (e) => {
         let messageConfirmation = confirm('Merci de cliquer sur OK pour confirmer');
         
         if(messageConfirmation) {
             // On récupere la valeur des checkbox qui sera le parametre de la route à exécuter.
             const inputValue = input.getAttribute('value');
 
             // On créer l'url de la route à executer pour changer l'état d'une franchise.
             const urlActivateFranchise = 'http://127.0.0.1:8000/franchises/changer-etat-' + inputValue
         
             // On commence le traitement ajax
             let data = new FormData();
             xhr = new XMLHttpRequest();
 
             // On exectue la fonction qui va traiter la requete
             inFctOnReadyStateChange();
 
             xhr.open('POST', urlActivateFranchise, true);
             xhr.responseType = 'json';
             xhr.send(data);
         } else {
             e.preventDefault();
         }
     })   
 });
 
 
 
 /**
  * CHANGEMENT D'ÉTAT DES PERMISSIONS GLOBALES D'UNE FRANCHISE (EN AJAX).
  * 
  */
 // On récupère tous les inputs contenu dans un élément avec l'id state-global-permission.
  const inputStateglobalPermission = document.querySelectorAll('#state-global-permission input[type=checkbox]')
 
  inputStateglobalPermission.forEach(input => {
     input.addEventListener('click', (e) => {
         // On demande la confirmation à l'utilisateur.
         let messageConfirmation = confirm('Merci de cliquer sur OK pour confirmer');
 
         if(messageConfirmation) {
         // On récupere la valeur des checkbox qui sera le parametre de la route à exécuter.
         const inputValueGP = input.getAttribute('value');
         const idFranchise = input.dataset.idFranchise;;
         console.log(idFranchise);
 
         // On créer l'url de la route à executer avec les paramètre
         const urlchangePermission = 'http://127.0.0.1:8000/franchises/changer-permission-globale-' + idFranchise + '-' +inputValueGP;
     
         // On commence le traitement ajax
         let data = new FormData();
         xhr = new XMLHttpRequest();
 
         // On exectue la fonction qui va traiter la requete
         inFctOnReadyStateChange();
 
         xhr.open('POST', urlchangePermission, true);
         xhr.responseType = 'json';
         //xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
         xhr.send(data);
         } else {
             e.preventDefault();
         }
 
     })   
 });
 
 
 
 /**
  * CHANGEMENT DU TEXTE ACTIVE INACTIVE SUIVANT L'ÉTAT DE LA CHECKBOX.
  * 
  * On récupère uniquement les checkbox qui ont leur parent avec un frère qui a comme class
  * state-checkbox-text. 
  */
 let stateCheckboxText = document.querySelectorAll('.state-checkbox-text')
 let checkboxHasStateText = document.querySelectorAll('.state-checkbox-text + * > input[type=checkbox]');
 
 for(let i = 0; i < checkboxHasStateText.length; i++) {
     checkboxHasStateText[i].addEventListener('click', () => {  
 
         if(checkboxHasStateText[i].checked) {
             stateCheckboxText[i].innerText = 'Active';
         } else {
             stateCheckboxText[i].innerText = 'Inactive';
         }
     })
 }
 
 
 
 /**
  * SUPPRESSION DE LA PHOTO DE PROFIL (EN AJAX).
  * 
  */
  const btnDeleteAvatar = document.querySelector('.delete-avatar');
  let userPicture = document.querySelector('.change-avatar-profil img');
  const avatarByDefault = '/images/avatar/avatar-defaut.jpg';
 
  btnDeleteAvatar.addEventListener('click', (e) => {
     let messageConfirmation = confirm('Voulez-vous vraiment supprimer votre photo ?');
     
     if(messageConfirmation) {
         // On créer l'url de la route à executer avec les paramètre.
         const urlDeleteAvatar = 'http://127.0.0.1:8000/profil/supprimer-avatar';
     
         // Message à afficher si la photo est celle par défaut.
         if(userPicture.getAttribute('src') == avatarByDefault) {
             message = 'Impossible de supprimer la photo par defaut.';
             displayPopup(message, typePopup = 'notice');
             
             return false
         }
 
         // On commence le traitement ajax.
         let data = new FormData();
         xhr = new XMLHttpRequest();
 
         // On exectue la fonction qui va traiter la requete
         inFctOnReadyStateChange();
 
         xhr.open('POST', urlDeleteAvatar, true);
         xhr.responseType = 'json';
         xhr.send(data);
     } else {
         e.preventDefault();
     } 
 });
 
 
 
 /**
  * VALIDE LA TAILLE DE LA PHOTO;
  * Affiche le nom de la photo séléctionnée par l'utilisateur.
  * Indique si la photo séléctionnée respecte la taille de 2MO.
  * 
  */
 const btnSelectAvatar = document.querySelector('.select-avatar');
 const detailSelectAvatar = document.querySelector('.detail-select-avatar');
 
 btnSelectAvatar.addEventListener('input', (e) => {
     let namePictureSend = e.target.files[0].name;
     let sizePictureSend = e.target.files[0].size
     btnSelectAvatar.style.background = '';
 
     if (sizePictureSend > 2000000) {
         message = 'La photo séléctionnée dépasse les 2Mo';
         displayPopup(message, typePopup = 'notice');
     } else {
         //detailSelectAvatar.textContent = 'Vous avez séléctionné : ' + namePictureSend;
         detailSelectAvatar.textContent = namePictureSend + ' : Cliquer sur le bouton enregistrer pour confirmer';
         detailSelectAvatar.style.color = '#2668e2';
     }
 })
 
 
 