import { changeStateElement } from './app.js';
import { displayPopup } from './app.js';


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

                // Les paramètres id et name ne sont jamais appelés en même temps et sont toujours mis en fin d'url, on récupère la dernière valeur.
                const getParamInput = arrayParams.pop();
               
                // On injecte la valeur dans l'input correspondant.
                if (params.includes('name')) {
                    inputName.setAttribute('value', getParamInput);
                } else {
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
function loadNewContent(url) {
    // Contenu à changer.
    const contentJS = document.querySelector('.form-state-franchise');
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

        if (this.readyState == 3) {

        } else if (this.readyState == 4 && this.status == 200) {
            // Si la requete c'est bien passée on remplace notre contenu.
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
        }
    };
    xhr.open('GET', urlAjax, true);
    xhr.responseType = 'json';
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest')
    xhr.send();
}



/**
 * RECHERCHER UNE FRANCHISE PAR ID OU NOM
 * 
 */
// On récupère la balise contenant les inputs de recherche.
const tagSearchElement = document.querySelector('.block-search');

const inputName = document.querySelector('#name');
const inputId = document.querySelector('#id');

tagSearchElement.addEventListener('input', (e) => {
    const input = e.target.closest('input');

    // On séléctionne le champs qui doit effectuer la recherche (nom ou id).
    if (input.id == 'name') {
        e.preventDefault();

        // Si le champs ID comporte du texte, on le supprime.
        if (inputId.value != '') {
            inputId.value = '';
        }

        // On récupère la valeur saisie dans le champs.
        let valueInput = e.target.value;

        // On crée l'url de la requete.
        const baseUrl = window.origin + window.location.pathname;
        const paramName = '?name=' + valueInput;
        let urlFinal = baseUrl + paramName

        // On supprime le paramName de l'url si l'utilisateur efface toute sa saisie.
        if (valueInput === '') {
            urlFinal = baseUrl
        }

        // On appel la fonction qui va charger le nouveau contenu.
        loadNewContent(urlFinal);

    } else if (input.id == 'id') {
        e.preventDefault();
        // Si le champs ID comporte du texte, on le supprime.
        if (inputName.value != '') {
            inputName.value = '';
        }

        // On récupère la valeur saisie dans le champs.
        let valueInput = e.target.value;

        // On envoi pas le requete si la valeur n'est pas un nombre.
        if (isNaN(valueInput)) {
            const message = 'Vous devez entrer un nombre';
            displayPopup(message, 'notice', 2000)
            return false;
        }

        // On crée l'url de la requete.
        const baseUrl = window.origin + window.location.pathname;
        const paramName = '?id=' + valueInput;
        let urlFinal = baseUrl + paramName;

        // On supprime le paramID de l'url si l'utilisateur efface toute sa saisie.
        if (valueInput === '') {
            urlFinal = baseUrl;
        }

        // On appel la fonction qui va charger le nouveau contenu.
        loadNewContent(urlFinal);
    }
})



