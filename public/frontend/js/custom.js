// to get current year
function getYear() {
    var currentDate = new Date();
    var currentYear = currentDate.getFullYear();
    var displayYear = document.querySelector("#displayYear");
    if (displayYear) {
        displayYear.innerHTML = currentYear;
    }
}

getYear();