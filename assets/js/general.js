/**** JS COMMUN À L'ENSEMBLE DU SITE. ****/

/**
 * MENU MOBILE.
 * Au clic sur l'icone d'hamburger, on affiche le menu de navigation.
 */
 const iconHbrgMenu = document.querySelector('.hamburger-menu');
 const displayMenuMobile = document.querySelector('.display-menu-mobile');
 const iconCloseMenu = document.querySelector('.close-menu');
 const menuNavigation = document.querySelector('.menu-navigation');
 const overlayMenuMobile = document.createElement('div');
 
 if (iconHbrgMenu) {
     iconHbrgMenu.addEventListener('click', () => {
         // On supprime la classe qui cache le menu
         displayMenuMobile.classList.remove('display-menu-mobile');
 
         // On ajoute un backgroud sombre pour masquer légérement la page.
         overlayMenuMobile.classList.add('menu-mobile-overlay');
         menuNavigation.before(overlayMenuMobile);
 
         // Au clic sur l'icon de fermeture, on lance la fonction qui ferme le menu.
         iconCloseMenu.addEventListener('click', afterMenuClose)
 
         // Au clic sur le overlay, on lance la fonction qui ferme le menu.
         overlayMenuMobile.addEventListener('click', afterMenuClose)
     })
 }
 
 
 
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
         const linkMenu = li.getAttribute('href');
 
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
 
         if (!hasAccess) {
             const messagePopup = 'Vous n\'êtes pas autorisé à effectuer cette action';
             displayPopup(messagePopup, 'notice', 2000);
 
             e.preventDefault();
         }
     })
 }
 
 
 
 /**
  * AFFICHGAGE D'UNE POPUP.
  * 
  * @param {Message à afficher dans la popup} message message à afficher
  * @param {success, notice} typePopup type de message
  * @param {time = 8000} time temps d'affichage de la popup 
  * 
  */
 export function displayPopup(message, typePopup = 'success', time = 8000) {
     const popup = document.createElement('div');
     popup.innerText = message;
 
     if (typePopup === 'success') {
         popup.classList.add('pop-up', 'pop-up-success');
     } else if (typePopup === 'notice') {
         popup.classList.add('pop-up', 'pop-up-notice');
     }
 
     document.body.prepend(popup);
     setTimeout(() => {
         popup.remove();
     }, time)
 }
 
 
 
 /**
  * CHANGEMENT DU TEXTE ACTIVE INACTIVE SUIVANT L'ÉTAT DE LA CHECKBOX.
  * 
  * On récupère uniquement les checkbox qui ont leur parent avec un frère qui a comme class state-checkbox-text. 
  */
 const stateCheckboxText = document.querySelectorAll('.state-checkbox-text')
 const checkboxHasStateText = document.querySelectorAll('.state-checkbox-text + * > input[type=checkbox]');
 
 for (let i = 0; i < checkboxHasStateText.length; i++) {
     checkboxHasStateText[i].addEventListener('change', () => {
         if (checkboxHasStateText[i].checked) {
             stateCheckboxText[i].innerText = 'Active';
         } else {
             stateCheckboxText[i].innerText = 'Inactive';
         }
     })
 }
 
 
 
 /**
  * ACTIVER OU DÉSACTIVER UN OBJET EN AJAX.
  * - Franchises
  * - Permissions globales 
  * - Suppression de la photo de profil de l'utilisateur
  * - Suppression d'un partenaire
  * 
  * Demande la confirmation avant d'envoyer la requete.
  * Affiche un loader le temps du traitement.
  * Affiche une popup de success avec un message.
  * 
  * @param {*} event l'event de l'écouteur.
  * @param {*} queryUrl : l'url de la requete à envoyée.
  * @param {*} data : les données à envoyer.
  * @param {*} redirect : un lien de redirection si la requete c'est bien passée.
  * 
  */
 export function changeStateElement(event, queryUrl, data = '', redirect = null) {
     const messageConfirmation = confirm('Merci de cliquer sur OK pour confirmer');
 
     if (messageConfirmation) {
         // Le loader à afficher si la requete échoue.
         const loader = document.createElement('div');
 
         const xhr = new XMLHttpRequest();
         xhr.onreadystatechange = function () {
             const resultat = this.response;
 
             // On affiche le loader.
             loader.classList.add('loader');
             document.body.prepend(loader);
             
             // Si la requete c'est bien passée, on affiche un message.
             if (this.readyState == 4 && this.status == 200) {
                 const successMessage = 'Changement effectué avec succès'
 
                 // On simule un temps de traitement de 2sec.
                 setTimeout(() => {
                     // On supprime le loader et on affiche une popup de succes.
                     loader.remove();
                     displayPopup(successMessage);
                     // On redirige vers la page de redirection.
                     if (redirect != null) {
                         setTimeout(() => {
                             window.location.replace(redirect);
                         }, 4000);
                     }
                 }, 2000)
             } else if (this.readyState == 4) {
                 loader.remove();
                 let message = resultat.message;
                 if (message == undefined) {
                     message = 'Une erreur s\'est produite';
                 }
                 displayPopup(message, 'notice', 5000)
             }
         };
 
         xhr.open('POST', queryUrl, true);
         xhr.responseType = 'json';
         xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
         xhr.send(data);
     } else {
         event.preventDefault();
     }
 }
 
 
 
 /**
  * CHANGEMENT DE CONTENU SANS RECHARGER LA PAGE.
  * 
  * Au clic sur une balise avec une class link-js, on appel la fonction loadNewContent qui 
  * va afficher le nouveau contenu sur la page.
  * 
  */
 // On va agir sur les pages qui contiennent une classe ajax-content.
 const ajaxContent = document.querySelector('.ajax-content');
 
 // On met l'écouteur sur la page, pour éviter de le perdre sur le nouveau contenu à afficher.
 if (ajaxContent) {
     ajaxContent.addEventListener('click', (e) => {
         // On séléctionne l'élement ou le parent qui à la classe link-js
         const aLinkJS = e.target.closest('.link-js')
 
         // On met le click uniquement sur les classe link-js.
         if (aLinkJS) {
             e.preventDefault();
 
             // ON CONSTRUIT L'URL pour la requete Ajax: 
             // On récupère le href du lien cliqué + l'url d'origine.
             const hrefLink = aLinkJS.getAttribute('href');
             const url = window.origin + hrefLink;
 
             // On appel la fonction qui va charger le nouveau contenu.
             loadNewContent(url);
         }
     })
 
     // Si on actualise la page, on continu d'afficher les résultats de l'url.
     if (window.location.search != '') {
         window.addEventListener('load', () => {
             const url = window.location;
             loadNewContent(url)
 
             // On garde la valeur des paramètres name et id pour l'ajouter dans l'input.
             const params = window.location.search;
 
             if (params.includes('name') || params.includes('id')) {
                 // On coupe la chaine avec les =
                 const arrayParams = params.split('=');
 
                 // Les paramètres id et name ne sont jamais appelés en même temps et sont toujours mis en fin d'url, on récupère donc la dernière valeur.
                 const getParamInput = arrayParams.pop();
 
                 // On injecte la valeur dans l'input correspondant.
                 if (params.includes('name')) {
                     const inputName = document.querySelector('.ajax-content [name="name"]')
                     inputName.setAttribute('value', getParamInput);
                 } else {
                     const inputId = document.querySelector('.ajax-content [name="id"]')
                     inputId.setAttribute('value', getParamInput);
                 }
             }
         })
     }
 }
 
 
 
 /**
  * AFFICHE LES NOUVEAUX ELEMENTS SUR LA PAGE (EN AJAX).
  * 
  * Permet de changer les contenus de la page sans la recharger.
  * 
  * @param {*} url : prend l'url de la requete à éxecuter.
  */
 export function loadNewContent(url) {
     // Contenu à changer.
     const contentJS = document.querySelector('.js-content');
     const paginationJS = document.querySelector('.js-pagination');
     const filterJS = document.querySelector('.js-filter');
 
     const nbrResultatFilter = document.querySelector('.number-resultat-filter');
 
     // On remplace l'url du navigateur avec la nouvelle url.
     history.replaceState({}, null, url);
 
     // On ajoute un param à l'url pour palier au problème de cache avec le JSON. Lors du retour en arrière cela affiché la réponse au format JSON.
     let urlAjax = url + '&ajax=1'
     if (window.location.search === '') {
         urlAjax = url + '?ajax=1'
     }
 
     // On execute la requete Ajax.
     const xhr = new XMLHttpRequest();
     xhr.onreadystatechange = function () {
         const resultat = this.response;
 
         // Si la requete c'est bien passée on remplace notre contenu.
         if (this.readyState == 4 && this.status == 200) {
             contentJS.innerHTML = resultat.content;
             paginationJS.innerHTML = resultat.pagination;
             filterJS.innerHTML = resultat.filterState;
 
             // On affiche le nombre d'élément correspond à la recherche si un param est présent.
             if (window.location.search != '') {
                 const nbrElement = resultat.nbrAllElement;
                 if (nbrElement == 1) {
                     nbrResultatFilter.innerHTML = '<h2>' + nbrElement + ' résultat trouvé</h2>'
                 } else if (nbrElement == 0) {
                     nbrResultatFilter.innerHTML = '<h2>' + 'Aucun résultat trouvé</h2>'
                 } else if (nbrElement > 1) {
                     nbrResultatFilter.innerHTML = '<h2>' + nbrElement + ' résultats trouvés</h2>'
                 }
             } else {
                 nbrResultatFilter.textContent = ''
             }
         } else if (this.readyState == 4) {
             let message = resultat.message;
             if (message == undefined) {
                 message = 'Une erreur s\'est produite';
             }
             displayPopup(message, 'notice', 5000)
         }
     };
     xhr.open('GET', urlAjax, true);
     xhr.responseType = 'json';
     xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest')
     xhr.send();
 }
 
 