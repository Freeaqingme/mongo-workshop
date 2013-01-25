<?php
/* The file to parse will be on the command line */
$file = $argv[1];

/* Connect, empty the collection and create indexes */
$m = new MongoClient( 'mongodb://localhost:27017' );
$collection = $m->selectCollection( 'demo', 'poi' );
$collection->drop();
$collection->ensureIndex( array( 'loc' => '2d' ) );
$collection->ensureIndex( array( 'tags' => 1 ) );

/* Parse the nodes */
$z = new XMLReader();
$z->open( $argv[1]);
while ($z->read() && $z->name !== 'node' );
$count = 0;
$collection->remove( array( 'type' => 1 ) );

echo "Importing nodes:\n";
while ($z->name === 'node') {
    $dom = new DomDocument;
    $node = simplexml_import_dom($dom->importNode($z->expand(), true));

    /* #1: Create the document structure */
    $q = array();
    /* Add type, _id and loc elements here */



    /* Check the parseNode implementation */
    parseNode($q, $node);

    /* #2: Write the insert command here */
    $collecti on->insert($q);


    $z->next('node');
    $count++;
    if ($count % 1000 === 0) {
        echo ".";
    }
    if ($count % 100000 === 0) {
        echo "\n", $count, "\n";
    }
}
echo "\n";

/* Parse the ways */
$z = new XMLReader();
$z->open( $argv[1]);
while ($z->read() && $z->name !== 'way' );
$count = 0;
$collection->remove( array( 'type' => 2 ) );

echo "Importing ways:\n";
while ($z->name === 'way') {
    $dom = new DomDocument;
    $way = simplexml_import_dom($dom->importNode($z->expand(), true));

    /* #3: Create the document structure */
    $q = array();
    /* Add type and _id elements here */


    /* Check the fetchLocations() and parseNode() implementations */
    fetchLocations($collection, $q, $way);
    parseNode($q, $way);

    /* #4: Write the insert command here */


    $z->next('way');
    if (++$count % 100 === 0) {
        echo ".";
    }
    if ($count % 10000 === 0) {
        echo "\n", $count, "\n";
    }
}
echo "\n";

function fetchLocations($collection, &$q, $node)
{
    $tmp = $locations = $nodeIds = array();
    foreach ($node->nd as $nd) {
        $nodeIds[] = 'n' . (int) $nd['ref'];
    }
    $r = $collection->find( array( '_id' => array( '$in' => $nodeIds ) ) );
    foreach ( $r as $n ) {
        $tmp[$n["_id"]] = $n['loc'];
    }
    foreach ( $nodeIds as $id ) {
        if (isset($tmp[$id])) {
            $locations[] = $tmp[$id];
        }
    }
    $q['loc'] = $locations;
}

function parseNode(&$q, $sxml)
{
    $tagsCombined = array();
    $ignoreTags = array( 'created_by', 'abutters' );

    foreach( $sxml->tag as $tag )
    {
        if (!in_array( $tag['k'], $ignoreTags)) {
            $tagsCombined[] = (string) $tag['k'] . '=' . (string) $tag['v'];
        }
    }

    $q['tags'] = $tagsCombined;
}
