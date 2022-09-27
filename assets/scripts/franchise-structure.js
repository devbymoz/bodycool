import { changeStateElement } from './general.js';
import { displayPopup } from './general.js';
import { loadNewContent } from './general.js';


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




/**
 * CONFIRMATION AVANT SUPRESSION D'UN PARTENAIRE
 *  - Franchise
 *  - Structure
 */
const linkDeletePartner = document.querySelector('.delete-partner');

if (linkDeletePartner) {
    linkDeletePartner.addEventListener('click', (e) => {
        e.preventDefault();
        const messageConfirmation = confirm('Vous allez supprimer un partenaire, vous devrez confirmer une seconde fois.');

        if (messageConfirmation) {
            e.preventDefault();
            // On récupère l'url du lien de suppression.
            const url = linkDeletePartner.getAttribute('href');

            // On crée l'url de redirection.
            const pathname = window.location.pathname.split('/')[1];
            const redirect = window.location.origin + '/' + pathname;

            // On appel la fonction pour changer l'état d'un élément.
            changeStateElement(e, url, '', redirect)
        } else {
            e.preventDefault();
        }
    })
}




/**
 * MODIFICATION D'UNE FRANCHISE
 * La balise html doit contenir un attribut data, qui correspond au nom du paramètre à modifier.
 * 
 */
// On récupère le contenu qui peut etre édité.
const contentsEditable = document.querySelector('.content-editable');
if (contentsEditable) {
    contentsEditable.addEventListener('click', (e) => {
        // On agit uniquement les icones editables
        const iconEditable = e.target.closest('i')

        if (iconEditable) {
            // On récupère le champ à éditer et son contenu.
            const contentEditable = iconEditable.previousElementSibling;
            const oldContent = contentEditable.innerText;

            // On récupère la valeur de l'attribut data request.
            const nameRequest = contentEditable.dataset.request;

            // Au clic sur l'icon, on rend le champ editable et on lui met le focus.
            contentEditable.setAttribute('contenteditable', true);
            contentEditable.focus();
            contentEditable.style.padding = '8px 16px'

            // On traite la demande lorsque l'élément perd le focus, se déclenche une fois.
            contentEditable.addEventListener('blur', (e) => {
                contentEditable.removeAttribute('contenteditable');
                contentEditable.blur();
                contentEditable.style.padding = ''

                // On compare la nouvelle valeur a l'ancienne.
                const newContent = contentEditable.innerText;

                if (oldContent !== newContent) {
                    const messageConfirmation = confirm('Merci de cliquer sur OK pour confirmer');

                    if (messageConfirmation) {
                        // On crée l'url de la requete à envoyer.
                        const url = 'http://127.0.0.1:8000/franchises/modifier-franchise-52';

                        // On formate les données à envoyer.
                        const data = nameRequest + '=' + newContent;

                        // Le loader à afficher si la requete échoue.
                        const loader = document.createElement('div');

                        // On commence le traitement de la requete Ajax.
                        const xhr = new XMLHttpRequest();
                        xhr.onreadystatechange = function () {
                            const resultat = this.response;

                            // On affiche un loader le temps du traitement de la requete
                            if (this.readyState == 3) {
                                loader.classList.add('loader');
                                document.body.prepend(loader);
                                // Si la requete c'est bien passée, on affiche un message.
                            } else if (this.readyState == 4 && this.status == 200) {
                                const successMessage = 'Changement effectué avec succès'

                                // On simule un temps de traitement de 2sec.
                                setTimeout(() => {
                                    // On supprime le loader et on affiche une popup de succes.
                                    loader.remove();
                                    displayPopup(successMessage);
                                }, 2000)
                                // Si la nouvelle valeur existe deja en BDD
                            } else if (this.readyState == 4 && this.status == 409) {
                                loader.remove();
                                displayPopup(resultat.alreadyExists, 'notice', 5000);
                                // On remet l'ancienne valeur dans le contenu.
                                contentEditable.innerText = oldContent;
                            }

                        };
                        xhr.open('POST', url, true);
                        xhr.responseType = 'json';
                        xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
                        xhr.send(data);
                    } else {
                        e.preventDefault();
                        // On remet l'ancienne valeur dans le contenu.
                        contentEditable.innerText = oldContent;
                    }
                } else {
                    e.preventDefault()
                }

            }, { once: true })
        }
    })
}


