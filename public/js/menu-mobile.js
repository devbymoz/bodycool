let hbrgMenu = document.querySelector('.hamburger-menu');
let menuMobile = document.querySelector('.display-menu-mobile');
let closeMenu = document.querySelector('.close-menu');
let menuNavigation = document.querySelector('.menu-navigation');


/* Au click sur l'icon hamburger, on affiche le menu */
hbrgMenu.addEventListener('click', (e) => {
    menuMobile.classList.remove('display-menu-mobile');
    //document.body.style.background = 'red';

    console.log(e);
})


/* Au click sur l'icon de fermeture du menu, on le ferme */
closeMenu.addEventListener('click', () => {
    menuMobile.classList.add('display-menu-mobile');
})

