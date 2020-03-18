function show_status() {
    if (localStorage.getItem('access_token')) {
        $('#not-logged-in').hide();
        $('#logged-in').show();
    } else {
        $('#logged-in').hide();
        $('#not-logged-in').show();
    }
}

function logout() {
    localStorage.removeItem('access_token');
    localStorage.removeItem('cid');
    localStorage.removeItem('cartid');
    localStorage.removeItem('kind');
    localStorage.removeItem('addressid');
    window.location.href = 'index.html';
}

