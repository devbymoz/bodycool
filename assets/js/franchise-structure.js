const routes = require('../js/routes.json');
import Routing from '../../vendor/friendsofsymfony/jsrouting-bundle/Resources/public/js/router.min.js';
Routing.setRoutingData(routes);

import { changeStateElement } from './general.js';
import { displayPopup } from './general.js';
import { loadNewContent } from './general.js';

// Permet de savoir si l'utilisateur est un admin.
const userRole = document.querySelector('.js-user-access');
const hasAccess = userRole.dataset.hasAccess;



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
            const urlAjax = Routing.generate('app_changer_etat_franchise', { id: input })

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
            const urlAjax = Routing.generate('app_changer_permission_globale', { id: idFranchise, idGP: input })

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

            //On récupère le path de l'url pour vérifier quelle page est appelée.
            const pathname = window.location.pathname;
            let baseUrl = '';

            // On crée l'url de la requete en fonction de le page appelée.
            if (pathname.includes('/structures')) {
                baseUrl = Routing.generate('app_list_structure')
            } else if (pathname.includes('/franchises')) {
                baseUrl = Routing.generate('app_list_franchise')
            }

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
            // Si le champs Name comporte du texte, on le supprime.
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

            //On récupère le path de l'url pour vérifier quelle page est appelée.
            const pathname = window.location.pathname;
            let baseUrl = '';

            // On crée l'url de la requete en fonction de le page appelée.
            if (pathname.includes('/structures')) {
                baseUrl = Routing.generate('app_list_structure')
            } else if (pathname.includes('/franchises')) {
                baseUrl = Routing.generate('app_list_franchise')
            }

            // On crée l'url de la requete.
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
            const urlAjax = Routing.generate('app_changer_etat_structure', { id: input })

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
 * MODIFICATION D'UNE FRANCHISE OU STRUCTURE
 * La balise html doit contenir un attribut data, qui correspond au nom du paramètre à modifier.
 * Elle contient également un attribut id qui correspond à l'id de l'élément à modifier.
 * 
 */
// On récupère le contenu qui peut etre édité.
const contentsEditable = document.querySelector('.content-editable');
if (contentsEditable) {
    contentsEditable.addEventListener('click', (e) => {
        // Si l'utilisateur n'est pas un admin
        if (!hasAccess) {
            e.preventDefault();
        } else {
            // On agit uniquement les icones editables
            const iconEditable = e.target.closest('i')

            if (iconEditable) {
                // On récupère le champ à éditer et son contenu.
                const contentEditable = iconEditable.previousElementSibling;
                const oldContent = contentEditable.innerText;

                // On récupère la valeur de l'attribut data id.
                const idElement = contentEditable.dataset.id;

                // On récupère la valeur de l'attribut data request.
                const nameRequest = contentEditable.dataset.request;

                // Au clic sur l'icon, on rend le champ editable et on lui met le focus.
                contentEditable.setAttribute('contenteditable', true);
                contentEditable.focus();
                contentEditable.style.padding = '8px 16px'

                // On traite la demande lorsque l'élément perd le focus.
                contentEditable.addEventListener('blur', sendQuery, { once: true });

                function sendQuery() {
                    contentEditable.removeAttribute('contenteditable');
                    contentEditable.blur();
                    contentEditable.style.padding = ''

                    // On compare la nouvelle valeur a l'ancienne.
                    const newContent = contentEditable.innerText;

                    if (oldContent !== newContent) {
                        const messageConfirmation = confirm('Merci de cliquer sur OK pour confirmer');

                        if (messageConfirmation) {
                            //ON CRÉE L'URL DE LA REQUETE
                            //On récupère le path de l'url pour vérifier quelle page est appelée.
                            const pathname = window.location.pathname;
                            let url = '';

                            // On crée l'url de la requete en fonction de le page appelée.
                            if (pathname.includes('/structures')) {
                                url = Routing.generate('app_modifier_structure', { id: idElement })
                            } else if (pathname.includes('/franchises')) {
                                url = Routing.generate('app_modifier_franchise', { id: idElement })
                            }

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
                                        // On redirige si le changement a été effectué.
                                        setTimeout(() => {
                                            window.location.replace(window.location);
                                        }, 4000);
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
                }
            }
        }
    })
}


/**
 * CHANGEMENT D'ÉTAT DES PERMISSIONS D'UNE STRUCTURE (EN AJAX).
 * 
 */
// On interdit le changement pour les permissions globales de la structure
const isGPermissionsStructure = document.querySelectorAll('.card-permission.global input');
if (isGPermissionsStructure) {
    isGPermissionsStructure.forEach(input => {
        input.addEventListener('click', (e) => {
            const message = 'Il n\'est pas possible de désactiver une permission globale depuis la structure'
            displayPopup(message, 'notice', 4000);

            e.preventDefault();
        })
    });
}
// On récupère le noeud qui contient les inputs pour changer l'état des permissions.
const stateStructurePermission = document.querySelectorAll('.structure-permission');

if (stateStructurePermission) {
    stateStructurePermission.forEach(input => {
        input.addEventListener('click', (e) => {
            if (e.target.tagName === 'INPUT') {
                // On récupere la valeur des checkbox pour savoir quelle permission modifiée.
                const input = e.target.getAttribute('value');

                // On récupere la valeur des checkbox qui sera le parametre de la route à exécuter.
                const idStructure = e.target.dataset.idStructure;

                // On créer l'url de la route qui permet de modifier l'état de la permission globale.
                const urlAjax = Routing.generate('app_changer_permission_classique', { id: idStructure, idP: input })

                // On appel la fonction pour changer l'état.
                changeStateElement(e, urlAjax)
            }
        })
    });
}


/**
 * LIER UNE STRUCTURE À UNE NOUVELLE FRANCHISE.
 * 
 * Se fait en deux requetes : la première sert à afficher le select des franchises, la seconde envoi la nouvelle franchise pour le traitement PHP.
 * 
 */
const btnChangeFranchise = document.querySelector('.change-franchise');
if (btnChangeFranchise) {
    btnChangeFranchise.addEventListener('click', (e) => {
        const route = 'app_lier_structure';
        const param = 'idfr=';

        if (!hasAccess) {

        } else {
            editStructure(e, route, param);
        }
    })
}


/**
 * LIER UN NOUVEAU GESTIONNAIRE À UNE STRUCTURE EXISTANTE
 * 
 */
const btnChangeStructureAdmin = document.querySelector('.change-structure-admin');

if (btnChangeStructureAdmin) {
    btnChangeStructureAdmin.addEventListener('click', (e) => {
        const route = 'app_lier_gestionnaire';
        const param = 'iduser=';

        if (!hasAccess) {

        } else {
            editStructure(e, route, param);
        }
    })
}


/**
 * MODIFICATION D'UNE STRUCTURE 
 *
 * Se fait en deux requetes : la première sert à afficher le select des gestionnaire, la seconde envoi le nouveau gestionnaire pour le traitement PHP.
 * 
 * 
 * @param {*} e event du listener
 * @param {*} route Route qui permet de modifier l'élément.
 * @param {*} param Le parameètre sans la valeur qui sera récupéré pour le traitement PHP
 */
function editStructure(e, route, param) {
    // On récupère l'id de la structure courante.
    const dataStructureId = document.querySelector('.structure-id');
    const id = dataStructureId.dataset.structureId;

    // L'url de la requete Ajax pour afficher la liste des franchises.
    const url = Routing.generate(route, { id: id });

    const xhr = new XMLHttpRequest();
    xhr.onreadystatechange = function () {
        const resultat = this.response;

        // On affiche un loader le temps du traitement de la requete
        if (this.readyState == 3) {

            // Si la requete c'est bien passée, on affiche un message.
        } else if (this.readyState == 4 && this.status == 200) {
            // L'url pour la requete qui va modifier la franchise.
            const urlAjax = url + '?ajax=1';

            // On ajoute le selecteur à la page.
            document.body.insertAdjacentHTML('afterbegin', resultat.content);

            // On récupère les informations du nouveau selecteur.
            const popupEdit = document.querySelector('.popup-edit-content');
            const select = document.querySelector('#select-new-value');

            select.addEventListener('change', () => {
                // On récupère l'id du nouveau gestionnaire.
                const valueParam = select.value;
                const params = param + valueParam;

                // L'url de redirection si le changement a été effectué.
                const redirect = window.location;

                // On appel la fonction qui va traiter la requete Ajax.
                changeStateElement(e, urlAjax, params, redirect);
            })

            // Pour fermer le selecteur.
            popupEdit.addEventListener('click', (e) => {
                e.preventDefault();
                if (e.target.className == 'popup-edit-content') {
                    popupEdit.remove()
                } else if (e.target.tagName == 'A') {
                    popupEdit.remove()
                }
            })
        }
    };
    xhr.open('POST', url, true);
    xhr.responseType = 'json';
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhr.send();
}