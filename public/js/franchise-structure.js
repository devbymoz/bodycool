import { changeStateElement } from './app.js';
import { displayPopup } from './app.js';
import { loadNewContent } from './app.js';


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
 * RECHERCHER UNE FRANCHISE OU STRUCTURE PAR ID OU NOM
 * 
 */
// On récupère la balise contenant les inputs de recherche.
const tagSearchElement = document.querySelector('.block-search');

const inputName = document.querySelector('#name');
const inputId = document.querySelector('#id');

if (tagSearchElement) {
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
}




/**
 * CHANGEMENT D'ÉTAT D'UNE STRUCTURE (EN AJAX).
 * 
 */
// On récupère le noeud qui contient les inputs pour changer l'état d'une structure.
const stateStructure = document.querySelector('.state-structure');

if (stateStructure) {
    stateStructure.addEventListener('click', (e) => {
        if (e.target.tagName === 'INPUT') {
            // On récupere la valeur des checkbox pour savoir quelle structure modifiée.
            const input = e.target.getAttribute('value');


            // On créer l'url de la route qui permet de modifier l'état d'une structure.
            const urlAjax = 'http://127.0.0.1:8000/structures/changer-etat-' + input

            // On appel la fonction pour changer l'état.
            changeStateElement(e, urlAjax)
        }
    })
}



