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

class Createswfile implements ObserverInterface
{
    /**
     * change api
     * @param Observer $observer
     */

    public function __construct(ObjectManager $simiObjectManager)
    {
        $this->simiObjectManager = $simiObjectManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $scopeConfigInterface = $this->simiObjectManager
            ->get('\Magento\Framework\App\Config\ScopeConfigInterface');
        $icon = $scopeConfigInterface->getValue('simipwa/notification/icon_url')?
        $scopeConfigInterface->getValue('simipwa/notification/icon_url'):
        "https://www.simicart.com/skin/frontend/default/simicart2.0/images/simicart/logo2.png";
                       
        $swcontent = "

        self.addEventListener('install', function (event) {
                event.waitUntil(
                self.skipWaiting()
            );
        });

        self.addEventListener('activate', function(event) {
          var cacheWhitelist = ['v2'];

          event.waitUntil(
            caches.keys().then(function(keyList) {
              return Promise.all(keyList.map(function(key) {
                if (cacheWhitelist.indexOf(key) === -1) {
                  return caches.delete(key);
                }
              }));
            })
          );
        });

        self.addEventListener('fetch', function (event) {
            if (event.request.method !== 'POST' && 
                event.request.url.toString() &&
                event.request.url.toString().indexOf('/checkout/') === -1 &&
                event.request.url.toString().indexOf('/cart/') === -1 &&
                event.request.url.toString().indexOf('/key/') === -1) {
                event.respondWith(
                    caches.match(event.request)
                        .then(function (response) {
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
                                        .then(function (cache) {
                                            cache.put(event.request, responseToCache);

                                        });

                                    return response;
                                }
                            );
                        })
                );
            }
        });


        self.addEventListener('push', function(event) {
        var apiPath = './simipwa/index/message?endpoint=';
        event.waitUntil(
            registration.pushManager.getSubscription()
                .then(function(subscription) {
                    if (!subscription || !subscription.endpoint) {
                        throw new Error();
                    }

                    apiPath = apiPath + encodeURI(subscription.endpoint);
                    return fetch(apiPath)
                        .then(function(response) {
                            if (response.status !== 200){
                                console.log('Problem Occurred:'+response.status);
                                throw new Error();
                            }
                            return response.json();
                        })
                        .then(function(data) {
                            if (data.status == 0) {
                                console.error('The API returned an error.', data.error.message);
                                throw new Error();
                            }
                            //console.log(data);
                            var options = {};
                            var title = '';
                            if (data.notification.notice_title){

                                title = data.notification.notice_title;
                                var message = data.notification.notice_content;
                                var icon = '".$icon.
                                "';
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
                                    data: {
                                        url: '/'
                                    }
                                };
                            }

                            return self.registration.showNotification(title, options);
                        })
                        .catch(function(err) {
                            console.log(err);
                            return self.registration.showNotification('New Notification', {
                                data: {
                                    url: '/'
                                }
                            });
                        });
                })
            );
        });
        self.addEventListener('notificationclick', function(event) {
            event.notification.close();
            var url = event.notification.data.url;
            event.waitUntil(
                clients.matchAll({
                    type: 'window'
                })
                    .then(function(windowClients) {
                        for (var i = 0; i < windowClients.length; i++) {
                            var client = windowClients[i];
                            if (client.url === url && 'focus' in client) {
                                return client.focus();
                            }
                        }
                        if (clients.openWindow) {
                            return clients.openWindow(url);
                        }
                    })
            );
        });";
        try {
            $fileToSave = $this->simiObjectManager
                ->get('\Magento\Framework\App\Filesystem\DirectoryList')
                ->getPath(DirectoryList::ROOT) . \DIRECTORY_SEPARATOR . 'sw.js';
            if (file_exists($fileToSave)) {
                $this->simiObjectManager
                ->get('\Magento\Framework\Filesystem\Io\File')
                ->rm($fileToSave );
            }
            file_put_contents($fileToSave, $swcontent);
            chmod($fileToSave , 0777);

        } catch (\Exception $exception) {

        }

        if ($scopeConfigInterface->getValue('simipwa/homescreen/homescreen_enable')) {
            $title = $scopeConfigInterface->getValue('simipwa/homescreen/home_screen_title')?
                    $scopeConfigInterface->getValue('simipwa/homescreen/home_screen_title'):
                    'Title';
            $icon = $title = $scopeConfigInterface->getValue('simipwa/homescreen/home_screen_icon')?
                    $scopeConfigInterface->getValue('simipwa/homescreen/home_screen_icon'):
                    'https://www.simicart.com/skin/frontend/default/simicart2.0/images/simicart/logo2.png';
            $manifestContent = '{
              "short_name": "'.$title.'",
              "name": "'.$title.'",
              "icons": [
                {
                  "src": "'.$icon.'",
                  "sizes": "192x192",
                  "type": "image/png"
                },
                {
                  "src": "'.$icon.'",
                  "sizes": "256x256",
                  "type": "image/png"
                },
                {
                  "src": "'.$icon.'",
                  "sizes": "384x384",
                  "type": "image/png"
                },
                {
                  "src": "'.$icon.'",
                  "sizes": "512x512",
                  "type": "image/png"
                }
              ],
              "start_url": "/",
              "display": "standalone",
              "theme_color": "#3399cc",
              "background_color": "#ffffff",
              "gcm_sender_id" : "832571969235"
            }';
            $fileToSave = $this->simiObjectManager
                ->get('\Magento\Framework\App\Filesystem\DirectoryList')
                ->getPath(DirectoryList::ROOT) . \DIRECTORY_SEPARATOR . 'manifest.json';
            if (file_exists($fileToSave)) {
                $this->simiObjectManager
                ->get('\Magento\Framework\Filesystem\Io\File')
                ->rm($fileToSave );
            }
            file_put_contents($fileToSave, $manifestContent);
            chmod($fileToSave , 0777);
        } else {
            $fileToSave = $this->simiObjectManager
                ->get('\Magento\Framework\App\Filesystem\DirectoryList')
                ->getPath(DirectoryList::ROOT) . \DIRECTORY_SEPARATOR . 'manifest.json';
            if (file_exists($fileToSave)) {
                $this->simiObjectManager
                ->get('\Magento\Framework\Filesystem\Io\File')
                ->rm($fileToSave );
            }
        }
    }
}
