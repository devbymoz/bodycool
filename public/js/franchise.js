import { changeStateElement } from './app.js';


/**
 * CHANGEMENT D'ÉTAT D'UNE FRANCHISE (EN AJAX).
 * 
 */
// On récupère le noeud qui contient les inputs pour changer l'état d'une franchise.
const stateFranchise = document.querySelector('.state-franchise');

if (stateFranchise) {
    stateFranchise.addEventListener('click', (e) => {
        if (e.target.tagName === 'INPUT') {
            // On récupere la valeur des checkbox pour savoir quelle franchise modifiée.
            const input = e.target.getAttribute('value');

            // On créer l'url de la route qui permet de modifier l'état d'une franchise.
            const urlAjax = 'http://127.0.0.1:8000/franchises/changer-etat-' + input

            // On appel la fonction pour changer l'état.
            changeStateElement(e, urlAjax)
        }
    })
}


/**
 * CHANGEMENT D'ÉTAT DES PERMISSIONS GLOBALES D'UNE FRANCHISE (EN AJAX).
 * 
 */
// On récupère le noeud qui contient les inputs pour changer l'état des GP.
const stateGlobalPermission = document.querySelector('.state-global-permission');

if (stateGlobalPermission) {
    stateGlobalPermission.addEventListener('click', (e) => {
        if (e.target.tagName === 'INPUT') {
            // On récupere la valeur des checkbox pour savoir quelle permission modifiée.
            const input = e.target.getAttribute('value');

            // On récupere la valeur des checkbox qui sera le parametre de la route à exécuter.
            const idFranchise = e.target.dataset.idFranchise;

            // On créer l'url de la route qui permet de modifier l'état de la permission globale.
            const urlAjax = 'http://127.0.0.1:8000/franchises/changer-permission-globale-' + idFranchise + '-' + input;

            // On appel la fonction pour changer l'état.
            changeStateElement(e, urlAjax)
        }
    })
}



/**
 * CHANGEMENT DE CONTENU AU CLIC.
 * 
 * Au clic sur une balise avec une class link-js, on exécute la fonction qui va récupérer les nouveaux éléments en JSON et qui va les afficher à la place des anciens.
 * 
 */
// On va agir sur les pages qui contiennent une classe ajax-content.
const ajaxContent = document.querySelector('.ajax-content');

// On met l'event sur la page, pour éviter de perdre l'event sur le nouveau contenu à afficher.
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
}



/**
 * AFFICHE LES NOUVEAUX ELEMENTS SUR LA PAGE (EN AJAX).
 * 
 * Permet de changer les contenus de la page sans la recharger.
 * 
 * @param {*} url : prend l'url de la requete à éxecuter.
 */
function loadNewContent(url) {
    // Contenu à changer.
    const contentJS = document.querySelector('.form-state-franchise');
    const paginationJS = document.querySelector('.js-pagination');
    const filterJS = document.querySelector('.js-filter');

    const nbrResultatFilter = document.querySelector('.number-resultat-filter em');

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

        if (this.readyState == 3) {

        } else if (this.readyState == 4 && this.status == 200) {
            // Si la requete c'est bien passée on remplace notre contenu.
            contentJS.innerHTML = resultat.content;
            paginationJS.innerHTML = resultat.pagination;
            filterJS.innerHTML = resultat.filterState;

            // On affiche le nombre d'élément correspond à la recherche.
            const nbrElement = resultat.nbrElementFilter;
            if (nbrElement == 1) {
                nbrResultatFilter.textContent = nbrElement + ' franchise trouvée'
            } else if (nbrElement == 0) {
                nbrResultatFilter.textContent = 'Aucune franchise de trouvée'
            } else if (nbrElement > 1) {
                nbrResultatFilter.textContent = nbrElement + ' franchises trouvées'
            }
        }
    };
    xhr.open('GET', urlAjax, true);
    xhr.responseType = 'json';
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest')
    xhr.send();
}




