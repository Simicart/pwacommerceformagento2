<?php
/**
 * Created by PhpStorm.
 * User: scott
 * Date: 1/29/18
 * Time: 9:23 PM
 */

namespace Simi\Simipwa\Observer;

use Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;
use \Magento\Framework\ObjectManagerInterface as ObjectManager;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Module\Dir;

class Createswfile implements ObserverInterface
{
    public $moduleDir;

    public function __construct(
        ObjectManager $simiObjectManager,
        Dir $moduleDir
    ) {
        $this->simiObjectManager = $simiObjectManager;
        $this->moduleDir = $moduleDir;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $scopeConfigInterface = $this->simiObjectManager
            ->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $icon = $scopeConfigInterface->getValue('simipwa/notification/icon_url') ?
            $scopeConfigInterface->getValue('simipwa/notification/icon_url') :
            "https://www.simicart.com/skin/frontend/default/simicart2.0/images/simicart/logo2.png";

        $swcontent = "

        'use strict';
        self.addEventListener(
            'install', function (event) {
                event.waitUntil(
                    self.skipWaiting()
                );
            }
        );

        self.addEventListener(
            'fetch', function (event) {
                if (event.request.method !== 'POST' &&
                    event.request.url.toString() &&
                    event.request.url.toString().indexOf('/checkout/') === -1 &&
                    event.request.url.toString().indexOf('/cart/') === -1 &&
                    event.request.url.toString().indexOf('/key/') === -1) {
                    if(!self.navigator.onLine){
                        event.respondWith(
                            caches.match(event.request)
                                .then(
                                    function (response) {
                                        if (response) {
                                            return response;
                                        }

                                        var fetchRequest = event.request.clone();
                                        return fetch(fetchRequest).then(
                                            function (response) {
                                                if (!response || response.status !== 200 || response.type !== 'basic') {
                                                    return response;
                                                }

                                                var responseToCache = response.clone();
                                                caches.open('simipwa-cache')
                                                    .then(
                                                        function (cache) {
                                                            cache.put(event.request, responseToCache);

                                                        }
                                                    );
                                                return response;
                                            }
                                        );
                                    }
                                )
                        );
                    }

                }
            }
        );

        self.addEventListener(
            'push', function (event) {
                var apiPath = './simipwa/index/message?endpoint=';
                event.waitUntil(
                    registration.pushManager.getSubscription()
                        .then(
                            function (subscription) {
                                if (!subscription || !subscription.endpoint) {
                                    throw new Error();
                                }

                                apiPath = apiPath + encodeURI(subscription.endpoint);
                                return fetch(apiPath)
                                    .then(
                                        function (response) {
                                            if (response.status !== 200){
                                                throw new Error();
                                            }

                                            return response.json();
                                        }
                                    )
                                    .then(
                                        function (data) {
                                            if (data.status == 0) {
                                                console.error('The API returned an error.', data.error.message);
                                                throw new Error();
                                            }

                                            //console.log(data);
                                            var options = {};
                                            var title = '';
                                            var icon = data.notification.logo_icon;
                                            if (data.notification.notice_title){
                                                title = data.notification.notice_title;
                                                var message = data.notification.notice_content;
                                                var url = '/';
                                                if (data.notification.notice_url) {
                                                    url = data.notification.notice_url;
                                                }

                                                if (data.notification.image_url){
                                                    options['image'] = data.notification.image_url;
                                                }

                                                var data = {
                                                    url: url
                                                };
                                                options = {
                                                    body : message,
                                                    icon: icon,
                                                    data: data
                                                };
                                            } else {
                                                title = 'New Notification';
                                                options = {
                                                    icon : icon,
                                                    data: {
                                                        url: '/'
                                                    }
                                                };
                                            }

                                            return self.registration.showNotification(title, options);
                                        }
                                    )
                                    .catch(
                                        function (err) {
                                            console.log(err);
                                            return self.registration.showNotification(
                                                'New Notification', {
                                                    icon: icon,
                                                    data: {
                                                        url: '/'
                                                    }
                                                }
                                            );
                                        }
                                    );
                            }
                        )
                );
            }
        );
        self.addEventListener(
            'notificationclick', function (event) {
                event.notification.close();
                var url = event.notification.data.url;
                event.waitUntil(
                    clients.matchAll(
                        {
                            type: 'window'
                        }
                    )
                        .then(
                            function (windowClients) {
                                for (var i = 0; i < windowClients.length; i++) {
                                    var client = windowClients[i];
                                    if (client.url === url && 'focus' in client) {
                                        return client.focus();
                                    }
                                }

                                if (clients.openWindow) {
                                    return clients.openWindow(url);
                                }
                            }
                        )
                );
            }
        );

        ";
        try {
            $fileToSave = $this->simiObjectManager
                    ->get('\Magento\Framework\App\Filesystem\DirectoryList')
                    ->getPath(DirectoryList::ROOT) . \DIRECTORY_SEPARATOR . 'simipwa-sw.js';
            if (file_exists($fileToSave)) {
                $this->simiObjectManager
                    ->get('\Magento\Framework\Filesystem\Io\File')
                    ->rm($fileToSave);
            }
            file_put_contents($fileToSave, $swcontent);
            chmod($fileToSave, 0777);

        } catch (\Exception $exception) {

        }

        if ($scopeConfigInterface->getValue('simipwa/homescreen/homescreen_enable')) {
            $appName = $scopeConfigInterface->getValue('simipwa/homescreen/app_name') ?
                $scopeConfigInterface->getValue('simipwa/homescreen/app_name') :
                'Title';

            $appShortName = $scopeConfigInterface->getValue('simipwa/homescreen/app_short_name') ?
                $scopeConfigInterface->getValue('simipwa/homescreen/app_short_name') :
                'Short Title';
            $icon = $scopeConfigInterface->getValue('simipwa/homescreen/home_screen_icon') ?
                $scopeConfigInterface->getValue('simipwa/homescreen/home_screen_icon') :
                'https://www.simicart.com/skin/frontend/default/simicart2.0/images/simicart/logo2.png';

            $icon192 = $scopeConfigInterface->getValue('simipwa/homescreen/home_screen_icon_192');

            $icon256 = $scopeConfigInterface->getValue('simipwa/homescreen/home_screen_icon_256');

            $icon384 = $scopeConfigInterface->getValue('simipwa/homescreen/home_screen_icon_384');

            $themeColor = $scopeConfigInterface->getValue('simipwa/homescreen/theme_color') ?
                $scopeConfigInterface->getValue('simipwa/homescreen/theme_color') :
                '#2196F3';
            $backgroundColor = $scopeConfigInterface->getValue('simipwa/homescreen/backrgound_color') ? $scopeConfigInterface->getValue('simipwa/homescreen/backrgound_color') :
                '#ffffff';
            $manifestContent = '{
              "short_name": "' . $appShortName . '",
              "name": "' . $appName . '",
              "icons": [
                {
                  "src": "' . $icon192 . '",
                  "sizes": "192x192",
                  "type": "image/png"
                },
                {
                  "src": "' . $icon256 . '",
                  "sizes": "256x256",
                  "type": "image/png"
                },
                {
                  "src": "' . $icon384 . '",
                  "sizes": "384x384",
                  "type": "image/png"
                },
                {
                  "src": "' . $icon . '",
                  "sizes": "512x512",
                  "type": "image/png"
                }
              ],
              "start_url": "/",
              "display": "standalone",
              "theme_color": "' . $themeColor . '",
              "background_color": "'.$backgroundColor.'",
              "gcm_sender_id" : "832571969235"
            }';
            $fileToSave = $this->simiObjectManager
                    ->get('\Magento\Framework\App\Filesystem\DirectoryList')
                    ->getPath(DirectoryList::ROOT) . \DIRECTORY_SEPARATOR . 'simi-manifest.json';
            if (file_exists($fileToSave)) {
                $this->simiObjectManager
                    ->get('\Magento\Framework\Filesystem\Io\File')
                    ->rm($fileToSave);
            }
            file_put_contents($fileToSave, $manifestContent);
            chmod($fileToSave, 0777);
        } else {
            $fileToSave = $this->simiObjectManager
                    ->get('\Magento\Framework\App\Filesystem\DirectoryList')
                    ->getPath(DirectoryList::ROOT) . \DIRECTORY_SEPARATOR . 'simi-manifest.json';
            if (file_exists($fileToSave)) {
                $this->simiObjectManager
                    ->get('\Magento\Framework\Filesystem\Io\File')
                    ->rm($fileToSave);
            }
        }
        $this->createNotificationJs();
    }

    public function createNotificationJs()
    {
        $scopeConfigInterface = $this->simiObjectManager
            ->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $notificationPubKey = $scopeConfigInterface->getValue('simipwa/notification/public_key');
        $notificationJScontent = "
        var applicationServerPublicKey = '" . $notificationPubKey . "';

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
            var api = './simipwa/index/register';
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
        ";


       // try {
            $moduleViewPath = $this->moduleDir->getDir('Simi_Simipwa', Dir::MODULE_VIEW_DIR);
            $notificationDirPath = $moduleViewPath . \DIRECTORY_SEPARATOR . 'frontend' .
                \DIRECTORY_SEPARATOR . 'web' .
                \DIRECTORY_SEPARATOR . 'js';
            $fileToSave = $notificationDirPath . \DIRECTORY_SEPARATOR . 'notification.js';
            
        if (file_exists($fileToSave)) {
            $this->simiObjectManager
                ->get('\Magento\Framework\Filesystem\Io\File')
                ->rm($fileToSave);
        }
            file_put_contents($fileToSave, $notificationJScontent);

        //} catch (\Exception $exception) {

        //}
    }
}
