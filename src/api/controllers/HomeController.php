<?php

namespace PPS\api\controllers;

use PPS\http\Controller;
use PPS\decorators\Controller as RouteGroup;
use PPS\decorators\Get;

#[RouteGroup('/home')]
class HomeController extends Controller {

    #[Get('')]
    public function home(): string {
      $SOCKET_URL = getenv('SOCKET_URL');

      return <<<HTML
      <script>
         (() => {
            const ws = new WebSocket('{$SOCKET_URL}');

            const actions = {
               ask_identity(channel, type, data) {
                  const { id: socketId } = data;
                  
                  const user = {
                     id: 0,
                     firstname: 'Nicolas',
                     lastname: 'Choquet',
                     email: 'nchoquet@norsys.fr',
                     repos_pseudo: {
                        github: 'nicolachoquet06250',
                        gitlab: 'nicolachoquet06250'
                     },
                     followed_apps: [1]
                  };

                  ws.send(JSON.stringify({
                     channel: 'identity',
                     type: 'give',
                     data: { user, id: socketId }
                  }))
               },

               received_identity() {
                  console.log('identitée bien reçue');
               },

               give_notify(channel, type, data) {
                  console.log(data);
               }
            };
            
            ws.addEventListener('message', e => {
               const { data } = e;
               
               const { channel, type, data: _data } = JSON.parse(data);
               
               if (`\${type}_\${channel}` in actions) {
                  actions[`\${type}_\${channel}`](channel, type, _data);
               } else {
                  console.log(JSON.parse(data))
               }
            });

            setTimeout(() => {
               const location = window.location.protocol + '//' + window.location.host;

               fetch(location + '/app/1?socket={$SOCKET_URL}', {
                  method: 'PUT',
                  headers: {
                     'Content-Type': 'application/json'
                  }, 
                  body: JSON.stringify({
                     repo_type: 'github',
                     name: 'Budget Management 1',
                     nameSlug: 'budget-management',
                     repoName: 'budget-management-apk',
                     logo: 'https://thumbs.dreamstime.com/z/vecteur-d-ic%C3%B4ne-de-calcul-argent-budget-encaissant-le-logo-illustration-symbole-financier-paiement-152384739.jpg',
                     version: '0.1.0',
                     versionSlug: '0-1-0',
                     description: `apks signés générés pour l'application budget-management`,
                     stars: 3.5,
                     screenshots: [],
                     permissions: [],
                     categories: [
                           'budget',
                           'budgetaire',
                           'monnaitaire',
                           'argent',
                           
                     ],
                     comments: [
                           {
                              author: 1,
                              comment: 'Je suis très satisfait de cette application.',
                              note: 3.5,
                              date: '2021-11-24'
                           }
                     ],
                     author: 0
                  })
               }).then(r => r.json()).then(json => console.log(json));
            }, 4000);
         })()
      </script>
      HTML;
   }
}