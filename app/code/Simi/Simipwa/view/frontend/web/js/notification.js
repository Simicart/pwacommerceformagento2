var applicationServerPublicKey = 'BFn4qEo_D1R50vPl58oOPfkQgbTgaqmstMhIzWyVgfgbMQPtFk94X-ThjG0hfOTSAQUBcCBXpPHeRMN7cqDDPaE';

var isSubscribed = false;
var swRegistration = null;
var pushButton = null;


function urlB64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (var i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}


function initializeUI() {
    subscribeUser();
    // Set the initial subscription value
    swRegistration.pushManager.getSubscription()
        .then(function(subscription) {
            isSubscribed = !(subscription === null);
            //console.log(subscription);
            if (isSubscribed) {
                console.log('User IS subscribed.');
            } else {
                console.log('User is NOT subscribed.');
            }

            //updateBtn();
        });
}
function subscribeUser() {

    const applicationServerKey = urlB64ToUint8Array(applicationServerPublicKey);
    //console.log(applicationServerKey);
    swRegistration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: applicationServerKey
    })
        .then(function(subscription) {
            console.log('User is subscribed.');

            updateSubscriptionOnServer(subscription);

            isSubscribed = true;

            //updateBtn();
        })
        .catch(function(err) {
            console.log('Failed to subscribe the user: ', err);
            //updateBtn();
        });
}

function updateSubscriptionOnServer(subscription,type = 1) {
    // TODO: Send subscription to application server
    var api = "./simipwa/index/register";
    var method = 'POST';
    if (type === 2) {
        api = './simipwa/index/delete';
    }
    ConnectionApi(api,method,subscription);
}

function unsubscribeUser() {
    swRegistration.pushManager.getSubscription()
        .then(function(subscription) {
            if (subscription) {
                updateSubscriptionOnServer(subscription,2);
                return subscription.unsubscribe();
            }
        })
        .catch(function(error) {
            console.log('Error unsubscribing', error);
        })
        .then(function() {

            console.log('User is unsubscribed.');
            isSubscribed = false;

            updateBtn();
        });
}

function updateBtn() {
    if (Notification.permission === 'denied') {
        pushButton.disabled = true;
        updateSubscriptionOnServer(null);
        return;
    }

    pushButton.disabled = false;
}

function ConnectionApi(api,method = 'GET',params = null){
    var headers = new Headers({
        'Content-Type': 'application/x-www-form-urlencoded',
        'Access-Control-Allow-Origin': '*',
        // 'Access-Control-Allow-Methods': 'GET, POST, OPTIONS, PUT, PATCH, DELETE',
        // 'Access-Control-Allow-Headers': 'X-Requested-With,content-type',
        // 'Access-Control-Allow-Credentials': true,
    });
    var init = {cache: 'default', mode: 'cors'};
    init['method'] = method;
    if(params){
        params = JSON.stringify(params);
        init['body'] = params;
    }

    var _request = new Request(api, init);
    fetch(_request)
        .then(function (response) {
            if (response.ok) {
                return response.json();
            }
            throw new Error('Network response was not ok');
        })
        .then(function (data) {
           console.log(data);
        }).catch((error) => {
        //alert(error.toString());
        console.error(error);
    });
}