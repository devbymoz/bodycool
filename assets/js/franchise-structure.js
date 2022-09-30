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
 * On récupère l'id de la franchise à modifier via la valeur de l'input.
 * L'url de la requete correspond à la route pour modifier l'état d'une franchise 
 * à laquelle on ajoute en paramètre l'id de la franchise
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

            // On appel la fonction pour changer l'état d'un objet.
            changeStateElement(e, urlAjax)
        }
    })
}



/**
 * CHANGEMENT D'ÉTAT D'UNE STRUCTURE (EN AJAX).
 * 
 * On récupère l'id de la structure à modifier via la valeur de l'input.
 * L'url de la requete correspond à la route pour modifier l'état d'une structure 
 * à laquelle on ajoute en paramètre l'id de la structure
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
 * CHANGEMENT D'ÉTAT DES PERMISSIONS GLOBALES D'UNE FRANCHISE (EN AJAX).
 * 
 * On récupère l'id de la permission globale à modifier via la valeur de l'input.
 * Pour la franchise, on récupère son id via l'attribut data-id-franchise.
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

            // On appel la fonction pour changer l'état d'un objet.
            changeStateElement(e, urlAjax)
        }
    })
}



/**
 * RECHERCHER UNE FRANCHISE OU STRUCTURE PAR ID OU NOM (EN AJAX).
 * 
 * La recherche est la même pour une franchise ou structure, seule la route change.
 * Pour savoir quelle route appeler, on test le chemin de l'url, s'il contient /structures ou /franchises.
 * 
 * Une fois que nous avons défini la route et la recherche à éffectuer, on appel la fonction qui 
 * se charge d'afficher le nouveau contenu sur la page.
 * 
 */
// On récupère la balise contenant les inputs de recherche.
const tagSearchElement = document.querySelector('.block-search');

// On récupère l'id des champs de recherche name et id.
const inputName = document.querySelector('#name');
const inputId = document.querySelector('#id');

if (tagSearchElement) {
    tagSearchElement.addEventListener('input', (e) => {
        const input = e.target.closest('input');

        // On séléctionne le champs qui doit effectuer la recherche (nom ou id).
        if (e.target.tagName === 'INPUT') {
            e.preventDefault();

            // Si l'autre champ comporte du texte, on le supprime.
            if (input.id == 'id') {
                inputName.value = '';
            } else if (input.id == 'name') {
                inputId.value = '';
            }

            // On envoi pas le requete si la valeur du champ id n'est pas un nombre.
            if (isNaN(inputId.value)) {
                const message = 'Vous devez entrer un nombre';
                displayPopup(message, 'notice', 2000)
                return false;
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

            // On ajoute le paramètre à l'url en fonction du champs de recherche.
            let paramName = '';
            if (input.id == 'id') {
                paramName = '?id=' + valueInput;
            } else if (input.id == 'name') {
                paramName = '?name=' + valueInput;
            }

            let urlFinal = baseUrl + paramName

            // On supprime le paramName de l'url si l'utilisateur efface toute sa saisie.
            if (valueInput === '') {
                urlFinal = baseUrl
            }

            // On appel la fonction qui va charger le nouveau contenu sur la page.
            loadNewContent(urlFinal);
        }
    })
}



/**
 * CONFIRMATION AVANT SUPRESSION D'UN PARTENAIRE
 * 
 * Ceci ajoute une deuxième confirmation pour la suppression d'une franchise ou d'une structure.
 * 
 * Le lien de suppression est le href de la balise A pour supprimer un partenaire, on envoi ce lien
 * à la fonction pour changer l'état d'un objet.
 * 
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
 * INTERDICTION DE CHANGER L'ACCES DES PERMISSIONS GLOBALE DEPUIS UNE STRUCTURE
 * 
 * Il s'agit que d'une restriction coté front, on affiche juste un popup et on bloque le toggle.
 */
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



/**
 * CHANGEMENT D'ÉTAT DES PERMISSIONS D'UNE STRUCTURE (EN AJAX).
 * 
 * Pour changer une permission "classique", on récupère l'id de la permission via la valeur de la checkbox, 
 * et on récupère l'id de la structure grace à l'attribut data-id-structure.
 * 
 * Ces deux informations, sont les paramètres nécessaires pour modifier l'accès des permissions classiques.
 */
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
 * LIER UNE STRUCTURE À UNE NOUVELLE FRANCHISE (EN AJAX)
 * 
 * Ici, on appel la fonction qui va se charger de modifier une structure.
 * 
 */
const btnChangeFranchise = document.querySelector('.change-franchise');
if (btnChangeFranchise) {
    btnChangeFranchise.addEventListener('click', (e) => {
        const route = 'app_lier_structure';
        const param = 'idfr=';

        // On bloque la modification si l'utilisateur n'est pas un admin.
        if (hasAccess) {
            editStructure(e, route, param);
        }
    })
}



/**
 * LIER UN NOUVEAU GESTIONNAIRE À UNE STRUCTURE EXISTANTE (EN AJAX)
 * 
 * Ici, on appel la fonction qui va se charger de modifier une structure.
 * 
 */
const btnChangeStructureAdmin = document.querySelector('.change-structure-admin');
if (btnChangeStructureAdmin) {
    btnChangeStructureAdmin.addEventListener('click', (e) => {
        const route = 'app_lier_gestionnaire';
        const param = 'iduser=';

        // On bloque la modification si l'utilisateur n'est pas un admin.
        if (hasAccess) {
            editStructure(e, route, param);
        }
    })
}



/**
 * MODIFICATION DES INFOS D'UNE STRUCTURE VIA UN CHAMP SELECT (EN AJAX).
 *
 * Se fait en deux requetes : 
 * - La première sert à afficher le champ select. 
 * - La seconde envoi la modification pour le traitement.
 * 
 * On affiche une popup avec le champ de selection de la nouvelle valeur, une fois la valeur choisie
 * on affiche un message de confirmation, si l'utilisateur valide, on appel la fonction * * *   changeStateElement pour changer l'état d'un objet, puis on redirige vers la page de la structure.
 * 
 * @param {*} e l'event de l'écouteur.
 * @param {*} route Route qui va traiter la modification.
 * @param {*} param Le paramètre sans la valeur qui sera récupéré pour le traitement.
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

        // Si la requete c'est bien passée, on affiche un message.
        if (this.readyState == 4 && this.status == 200) {
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
        } else if (this.readyState == 4) {
            let message = resultat.message;
            if (message == undefined) {
                message = 'Une erreur s\'est produite';
            }
            displayPopup(message, 'notice', 5000)
        }
    };
    xhr.open('POST', url, true);
    xhr.responseType = 'json';
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhr.send();
}



/**
 * MODIFICATION DES INFOS D'UNE FRANCHISE OU STRUCTURE VIA UN CHAMP TEXT (EN AJAX).
 * 
 * Au click sur l'icone de modification, on transforme la balise qui précède l'icone en champ editable.
 * 
 * La balise html doit contenir un attribut data-request, qui correspond au nom du paramètre à modifier et également un attribut id qui correspond à l'id de l'objet à modifier.
 * 
 * La requete est envoyée lorsque la champs perd le focus.
 * 
 */
// On récupère le contenu qui peut etre édité.
const contentsEditable = document.querySelector('.content-editable');
if (contentsEditable) {
    contentsEditable.addEventListener('click', (e) => {
        // On bloque la modification si l'utilisateur n'est pas un admin.
        if (hasAccess) {
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

                // Au clic sur l'icone, on rend le champ editable et on lui met le focus.
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
                                        // On redirige si le changement a été effectué.
                                        setTimeout(() => {
                                            window.location.replace(window.location);
                                        }, 4000);
                                    }, 2000)
                                    // Si la nouvelle valeur existe deja en BDD
                                } else if (this.readyState == 4) {
                                    loader.remove();
                                    let message = resultat.message;
                                    if (message == undefined) {
                                        message = 'Une erreur s\'est produite';
                                    }
                                    displayPopup(message, 'notice', 5000)
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
