document.addEventListener("DOMContentLoaded", () => {
    const star1 = document.getElementById("star_1");
    star1.onmouseover = function()  {
        console.log("hover");
    };
    star1.onclick = function() {
        console.log("click");
};      
});

/*
document.getElementById("star_1").onmouseover = function()  {
        console.log("hover");
};

document.getElementById("star_1").onclick = function() {
        console.log("click");
};      
*/
