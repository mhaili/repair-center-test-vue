# Notes

## Ce que j'ai fait et pourquoi

### Partie 1 - Le devis

La première chose que j'ai faite c'est lire le README et le doc métier pour bien comprendre 

J'ai créé deux entités :

**Quote** c'est le devis en lui même. Il est lié à un ordre de réparation OR avec une relation OneToOne 1 1 car un OR a au maximum un seul devis et un devis n'apprtient à qu'un seul OR. Les totaux HT TVA et TTC ne sont pas stockés en base de données ils sont calculés directement depuis les lignes. Comme ça si on modifie une ligne les totaux sont toujours corrects sans avoir à penser à les mettre à jour.

**QuoteLine** c'est une ligne du devis. Une ligne peut être soit une pièce du catalogue soit de la main d'oeuvre. J'ai choisi de garder les deux dans la même table plutôt que de faire deux tables séparées parce que ça me semblait plus simple. Les colonnes qui ne s'appliquent pas selon le type sont juste null.

Pour les taux horaires MO (Tolerie 65 euros/h Peinture 70 euros/h Mecanique 60 euros/h) je les ai mis comme constantes dans QuoteLine. C'est suffisant ici mais je sais que dans un vrai produit ces taux changeraient selon l'atelier donc il faudrait les stocker en base.

**Les montants sont en centimes**

J'ai stocké tous les montants en centimes c'est a dire en entiers plutôt qu'en float
J'ai découvert en lisant le doc qu'il y avait une note d'avertissement sur les float
Le problème c'est que 0.1 + 0.2 en PHP ne donne pas exactement 0.3 a cause de la façon dont les ordi stockent les nbrs decimaux en travaillant en centimes avec des entiers il n'y a plus ce prob. La conversion en euros se fait uniquement au moment d'afficher (division par 100).



### Partie 2 - Ce que j'ai amélioré dans le code existant

En lisant le code  j'ai remarqué plusieurs choses 

**La validation des statuts copiée 3 fois**

Dans RepairOrderController la liste des statuts et les règles de transition étaient create() update() et updateStatus() donc si demain on ajoute un statut ou qu'une règle change il faut penser à modifier les 3 endroits donc j'ai créé un enum RepairOrderStatus qui regroupe tout ça en un seul endroit

**Les règles métier dans le controller**

Le controller savait qu'un OR annulé ne peut plus changer de statut
Ce genre de règle appartient à l'entité elle même pas au controller
J'ai déplacé ça dans une méthode transitionTo() sur RepairOrder
Maintenant le controller fait juste appel à cette méthode et c'est l'entité qui gère ses propres règles

**Le totalAmount en float**

L'entité RepairOrder avait un champ totalAmount float, ce champ n'avait plus d'utilité une fois qu'on a le devis car le total se calcule depuis les lignes donc je l'ai supprimé.

**Le typage des propriétés**

Les entités Customer Part et RepairOrder avaient toutes leurs propriétés sans type PHP (juste public $id public $name etc) donc j'ai ajouté les types corrects car ça reste mieux pour la gestion d'erreurs et pour la BDD 


### Partie 3 - Tests

J'ai testé le montant HT d'une ligne pièce le calcul avec remise la TVA à 20% et un montant main d'oeuvre
J'ai aussi testé les règles de transition de statut



## Ce que je ferais avec plus de temps

- Rendre les taux horaires configurables en base de données
- Ajouter une remise globale sur le devis en plus de la remise par ligne
- Ajouter un statut au devis (brouillon / envoyé au client / validé)
- Ajouter des tests plus de test
- pouvoir envoyer le devis au client 
- pouvoir integrer un champ où le client pourra le signer electroniquement son devis depuis son espace client (si y'en a )
- Améliorer les performances des requêtes API car en local les requêtes mettent beaucoup de temps à s'exécuter j'ai pas pu creuser je me demande si ça vient de ma machine 


## Outillage

J'ai utilisé Claude (IA) ponctuellement pendant le test principalement pour débloquer des erreurs d'environnement par exemple je savais que y'avait des commandes qui permettaient de générer les entités et contorollers direct  make:controller mais elle  générait un nom de classe invalide avec le préfixe Api/ et j'avais aussi un problème de BOM sur le fichier .env.local qui empêchait Symfony de démarrer. Ca m'a permis de ne pas perdre de temps sur des problèmes d'installation et de me concentrer sur le code métier.