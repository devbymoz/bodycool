let iconHbrgMenu = document.querySelector('.hamburger-menu');
let displayMenuMobile = document.querySelector('.display-menu-mobile');
let iconCloseMenu = document.querySelector('.close-menu');
let menuNavigation = document.querySelector('.menu-navigation');
let overlayMenuMobile = document.createElement('div');

/**
 * Au clique sur l'icone hamburger, on affiche le menu
 */
 iconHbrgMenu.addEventListener('click', () => {
    displayMenuMobile.classList.remove('display-menu-mobile');
    overlayMenuMobile.classList.add('menu-mobile-overlay');
    menuNavigation.before(overlayMenuMobile);

    /**
     * Au clique sur le overlay, on ferme le menu
     */
    overlayMenuMobile.addEventListener('click', afterMenuClose)

    /** 
     * Ferme le menu si on clique sur l'icon de fermeture
     */
    iconCloseMenu.addEventListener('click',afterMenuClose)
})

/**
 * Masque le menu et supprime le overlay
 */
function afterMenuClose() {
    displayMenuMobile.classList.add('display-menu-mobile');
    overlayMenuMobile.remove();
}


/**
 * Info bulle au passage de la souris sur les icones du menu 
 */
let liMenu = document.querySelectorAll('.menu-navigation ul > a, .logout-mobile a');
let spanMenu = document.createElement('span');

liMenu.forEach(li => {
    li.addEventListener('mouseenter', () => {
        if (window.innerWidth > 620 && window.innerWidth < 970) {
            li.prepend(spanMenu);
            spanMenu.classList.add('bulle-menu');
            spanMenu.innerHTML = li.textContent
        }

        /* Supprime la bulle lorsque la souris est en dehors de l'icon */
        li.addEventListener('mouseleave', () => {
            spanMenu.innerText = '';
            spanMenu.remove();
        })
    });
});


/**
 * Surbrillance des li du menu en fonction de l'url 
 */
let currentURI = window.location.pathname;

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
 * Changement du texte 'Active' et 'Inactive' suivant l'état de la checkbox
 */
let stateCheckbox = document.querySelectorAll('.state-checkbox');
let stateToggleTxt = document.querySelectorAll('.state-toggle-txt');

for (let i = 0; i < stateCheckbox.length; i++) {
    stateCheckbox[i].addEventListener('change', ()=> {
        if (stateCheckbox[i].checked) {
            stateToggleTxt[i].innerText = 'Active';
        } else {
            stateToggleTxt[i].innerText = 'Inactive';
        }
    })
}

