# play-private-store-backend
api + websocket de play-private-store avec PHP 8.1

### CLIENT
```html
<!DOCTYPE html>
<html>
   <head>
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <meta name="viewport" content="width=device-width,initial-scale=1.0">
      <link rel="icon" href="<%= BASE_URL %>favicon.ico">
      <title> Stricte minimum - connexion websocket </title>
   </head>

   <body>

   </body>

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
            
            if (`${type}_${channel}` in actions) {
               actions[`${type}_${channel}`](channel, type, _data);
            } else {
               console.log(JSON.parse(data))
            }
         })
      })()
   </script>
</html>
```
