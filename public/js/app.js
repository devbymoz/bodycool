const iconHbrgMenu = document.querySelector('.hamburger-menu');
const displayMenuMobile = document.querySelector('.display-menu-mobile');
const iconCloseMenu = document.querySelector('.close-menu');
const menuNavigation = document.querySelector('.menu-navigation');
const overlayMenuMobile = document.createElement('div');

/**
 * Permet d'afficher le menu sur mobile, en cliquant sur l'icon hamburger.
 */
 iconHbrgMenu.addEventListener('click', () => {
    displayMenuMobile.classList.remove('display-menu-mobile');
    overlayMenuMobile.classList.add('menu-mobile-overlay');
    menuNavigation.before(overlayMenuMobile);

    /**
     * Au clic sur le overlay, on lance la fonction qui ferme le menu.
     */
    overlayMenuMobile.addEventListener('click', afterMenuClose)

    /** 
     * Au clic sur l'icon de fermeture, on lance la fonction qui ferme le menu.
     */
    iconCloseMenu.addEventListener('click',afterMenuClose)
})
/**
 * Permet de fermer le menu et de supprimer l'overlay
 */
function afterMenuClose() {
    displayMenuMobile.classList.add('display-menu-mobile');
    overlayMenuMobile.remove();
}


/**
 * Affiche une info bulle en passant la souris sur les icones du menu, uniquement lorsque le menu est au format icone seule.
 */
const liMenu = document.querySelectorAll('.menu-navigation ul > a, .logout-mobile a');
const spanMenu = document.createElement('span');

liMenu.forEach(li => {
    li.addEventListener('mouseenter', () => {
        if (window.innerWidth > 620 && window.innerWidth < 970) {
            li.prepend(spanMenu);
            spanMenu.classList.add('bulle-menu');
            spanMenu.innerHTML = li.textContent
        }

        /**
         * Supprime l'info bulle lorsque la souris n'est plus sur l'icone.
         */
        li.addEventListener('mouseleave', () => {
            spanMenu.innerText = '';
            spanMenu.remove();
        })
    });
});


/**
 * Permet de mettre les élements du menu en surbrillance lorsqu'on est sur une page du menu.
 */
const currentURI = window.location.pathname;

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
 * Bloquer la modification des activations et désactivations aux non amdin.
 * On récupère toutes les balises qui ont une classes no-access.
 * L'attribut data hasAccess renvoi true si l'utilisateur est un admin.
 * On affiche une popup si l'utilisateur clique sur un input auquel il n'a pas acces.
 */
const noAccess = document.querySelectorAll('.no-access');
const userRole = document.querySelector('.js-user-access');
const hasAccess = userRole.dataset.hasAccess;
const popupAlert = document.createElement('div');

for (let i = 0; i < noAccess.length; i++) {
    noAccess[i].addEventListener('click', (e) => {

        if (hasAccess === 'false') {
            popupAlert.classList.add('pop-up-alert');
            popupAlert.innerText = 'Vous ne pouvez pas effectuer cette action';

            document.body.prepend(popupAlert);
            setTimeout(() => {
                popupAlert.remove();
            }, 4000)

            e.preventDefault();
        }
    })
}


/**
 * Permet d'enregistrer les modifications sans cliquer sur le bouton submit.
 * Demande la confirmation avant de valider les modification.
 */
const formEditFranchise = document.querySelectorAll('.form-edit-franchise input');
const btnFormEditFranchise = document.querySelector('.form-edit-franchise button')

formEditFranchise.forEach(input => {
    input.addEventListener('click', (e) => {
        let messageConfirmation = confirm('Merci de cliquer sur OK pour confirmer');

        if(messageConfirmation) {
            btnFormEditFranchise.click();
        } else {
            e.preventDefault();
        }
    })
});



/**
 * Requete Ajax pour changer l'état d'une franchise.
 * 
 */
const inputState = document.querySelectorAll('#form-active-franchise input[type=checkbox]')
const popupAlertStateFranchise = document.createElement('div');
const loader = document.createElement('div');

inputState.forEach(input => {
    input.addEventListener('click', (e) => {
        // On demande la confirmation à l'utilisateur.
        let messageConfirmation = confirm('Merci de cliquer sur OK pour confirmer');

        if(messageConfirmation) {
        // On récupere la valeur des checkbox qui sera le parametre de la route à exécuter.
        const inputValue = input.getAttribute('value');

        // On créer l'url de la route à executer avec le param
        const urlActivateFranchise = 'http://127.0.0.1:8000/franchise/etat-franchise-' + inputValue
    
        // On commence le traitement ajax
        let data = new FormData();
        xhr = new XMLHttpRequest();

        xhr.onreadystatechange = function () {
            let resultat = this.response;

            // On affiche un loading le temps du traitement de la requete
            if (this.readyState == 3) {
                loader.classList.add('loader');
                document.body.prepend(loader);
            } else if (this.readyState == 4 && this.status == 200) {
                // Si la requete c'est bien passée.
                let franchiseName = resultat.franchiseName;
                let stateFranchise = resultat.newStateFranchise ? 'activée' : 'désactivée';
                let successMessage = 'La franchise : ' + franchiseName + ' a bien été ' + stateFranchise + '. Un mail va être envoyé au propriétaire.';

                // On simule un temps de traitement de 2sec.
                setTimeout(() => {
                    // On affiche une popup de succes.
                    loader.remove();
                    popupAlertStateFranchise.innerText = successMessage;
                    popupAlertStateFranchise.classList.add('pop-up-alert');
                    document.body.prepend(popupAlertStateFranchise);
                    setTimeout(() => {
                        popupAlertStateFranchise.remove();
                    }, 8000)
                }, 2000)
            } else if (this.readyState == 4) {
                console.log(resultat);
            } 
        };

        xhr.open('POST', urlActivateFranchise, true);
        xhr.responseType = 'json';
        //xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send(data);
        } else {
            e.preventDefault();
        }

    })   
});


