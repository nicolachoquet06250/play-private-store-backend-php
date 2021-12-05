<?php

namespace PPS\api\controllers;

use PPS\http\Controller;
use PPS\decorators\Controller as RouteGroup;
use PPS\decorators\Get;

#[RouteGroup('/')]
class HomeController extends Controller {

    #[Get('')]
    public function home(): string {
        return <<<HTML
    <script>
        (() => {
         const ws = new WebSocket('ws://localhost:8001/ws');

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
         })
      })()
    </script>
HTML;
    }
}