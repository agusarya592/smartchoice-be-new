<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use GuzzleHttp\Client;

class MasterController extends Controller
{
    /**
     * Retrieve the user for the given ID.
     *
     * @param  int  $id
     * @return Response
     */
    public function getMerk()
    {
        $data = [];
        $query = 'SELECT * WHERE {?merk rdfs:subClassOf smartphone:Smartphone .} ORDER BY ?merk';
        $merk = $this->sparql->query($query);
        
        foreach ($merk as $item) {
            $brandName = str_replace('_', ' ', $this->parseData($item->merk->getUri()));
            $data[] = ['name' => $brandName];
        }

        return response()->json([
            'data'=>$data, 
            'query'=>$query
        ]);
    }
    public function getCategories()
    {
        $data = [];
        $resultHarga = [];
        $resultPrioritas = [];
        $resultMemori = [];
        $resultRAM = [];
        $resultChipset = [];

        $harga = $this->sparql->query('SELECT * WHERE{?harga a smartphone:Harga}');
        $prioritas = $this->sparql->query('SELECT * WHERE{?prioritas a smartphone:PrioritasKegunaan}');
        $memori = $this->sparql->query('SELECT * WHERE {?memori a smartphone:Memori}');
        $ram = $this->sparql->query('SELECT * WHERE{?ram a smartphone:RAM}');
        $chipset = $this->sparql->query('SELECT * WHERE{?chipset a smartphone:Chipset}');
        
        foreach ($harga as $item) {
            $d = $this->parseData($item->harga->getUri());
            array_push($resultHarga, $d);
        }
        foreach ($prioritas as $item) {
            $d = $this->parseData($item->prioritas->getUri());
            array_push($resultPrioritas, $d);
        }
        foreach ($memori as $item) {
            $d = $this->parseData($item->memori->getUri());
            array_push($resultMemori, $d);
        }
        foreach ($ram as $item) {
            $d = $this->parseData($item->ram->getUri());
            array_push($resultRAM, $d);
        }
        foreach ($chipset as $item) {
            $d = $this->parseData($item->chipset->getUri());
            array_push($resultChipset, $d);
        }

        return response()->json([
            'data'=>[
                'harga'=>$resultHarga,
                'prioritas'=>$resultPrioritas,
                'memori'=>$resultMemori,
                'RAM'=>$resultRAM,
                'chipset'=>$resultChipset,
            ], 
        ]);
    }

    public function jelajah(Request $request, $merk)
    {
        $data = [];
        $query='SELECT * WHERE{?smartphone a smartphone:'.$merk.'  .?smartphone smartphone:seri_smartphone ?seri .?smartphone smartphone:gambar ?gambar}';
        $smartphones = $this->sparql->query($query);
        
        foreach ($smartphones as $item) {
            $brandName = $this->parseData($item->smartphone->getUri());
            $gambar = $this->parseData($item->gambar->getValue());
            $seri = $this->parseData($item->seri->getValue());
            $data[] = [
                'name' => $brandName,
                'gambar' => $gambar,
                'seri' => $seri,
            ];
        }

        return response()->json([
            'data'=>$data, 
            'query'=>$query
        ]);
    }

    public function detail(Request $request, $individual)
    {
        $data = [];
        $query = 'SELECT ?property ?value WHERE { smartphone:'.$individual.' ?property ?value .
            FILTER(isLiteral(?value) || isIRI(?value))
            FILTER(?property != rdf:type && ?property != rdfs:label) }';
        $smartphone = $this->sparql->query($query);

        foreach ($smartphone as $item) {
            $property = $this->parseData($item->property->getUri());
            $value="";
            try {
                $value = $this->parseData($item->value->getValue());
            } catch (\Throwable $th) {
                $value = $this->parseData($item->value->getUri());
            }
            $data[$property] = $value;
        }

        return response()->json([
            'data' => $data, 
            'query' => $query
        ]);

    }

    public function cari(Request $request)
    {
        $query = 'SELECT * WHERE {';
            $i = 0;
            if($request->harga != ''){
                $query = $query . '?smartphone smartphone:memilikiHarga smartphone:' . $request->harga;
                $i++;
            }
            if ($request->prioritas!= '') {
                if ($i == 0) {
                    $query = $query . '?smartphone smartphone:memilikiPrioritasKegunaan smartphone:' . $request->prioritas;
                    $i++;
                }
                else{
                    $query = $query . '. ?smartphone smartphone:memilikiPrioritasKegunaan smartphone:' . $request->prioritas;
                }
            }
            if ($request->memori!= '') {
                if ($i == 0) {
                    $query = $query . '?smartphone smartphone:memilikiMemori smartphone:' . $request->memori;
                    $i++;
                }
                else{
                    $query = $query . '. ?smartphone smartphone:memilikiMemori smartphone:' . $request->memori;
                }
            }
            if ($request->ram!= '') {
                if ($i == 0) {
                    $query = $query . '?smartphone smartphone:memilikiRAM smartphone:' . $request->ram;
                    $i++;
                }
                else{
                    $query = $query . '. ?smartphone smartphone:memilikiRAM smartphone:' . $request->ram;
                }
            }
            if ($request->chipset!= '') {
                if ($i == 0) {
                    $query = $query . '?smartphone smartphone:memilikiChipset smartphone:' . $request->chipset;
                    $i++;
                }
                else{
                    $query = $query . '. ?smartphone smartphone:memilikiChipset smartphone:' . $request->chipset;
                }
            }

            $query = $query . '.?smartphone smartphone:seri_smartphone ?seri .?smartphone smartphone:gambar ?gambar .?smartphone rdf:type ?merk. FILTER(?merk != smartphone:Smartphone) FILTER(?merk != owl:NamedIndividual)}';
            $queryData = $this->sparql->query($query);
            $data = [];
            if ($i === 0) {
                $data = [];
            } else {
                foreach ($queryData as $item) {
                    array_push($data, [
                        'merk' => $this->parseData($item->merk->getUri()),
                        'name' => $this->parseData($item->smartphone->getUri()),
                        'seri' => $this->parseData($item->seri->getValue()),
                        'gambar' => $this->parseData($item->gambar->getValue())
                    ]);
                }
            }
            return response()->json([
                'data' => $data,
                'query' => $query
            ]);
    }

    public function recommendations(Request $request)
    {
        $client = new Client();
        $response = $client->request('GET', 'http://127.0.0.1:5000/recommendations?user_id='.$request->user_id.'&n='.$request->n);
        $jsonData = json_decode($response->getBody(), true);
        $smartphonesString = "()";

        if (isset($jsonData['Recommended Items'])) {
            $recommendedItems = $jsonData['Recommended Items'];
            
            $smartphones = [];
            foreach ($recommendedItems as $item) {
                if (isset($item['Smartphone_IRI'])) {
                    $smartphones[] = 'smartphone:'.$item['Smartphone_IRI'];
                }
            }

            $smartphonesString = '(' . implode(', ', $smartphones) . ')';
        } else {
            echo "No Recommended Items found in the JSON data.";
        }

        $data = [];
        $query='SELECT * WHERE { ?smartphone a smartphone:Smartphone ; smartphone:seri_smartphone ?seri ; smartphone:gambar ?gambar . FILTER (?smartphone IN '.$smartphonesString.')}';
        $smartphones = $this->sparql->query($query);
        
        foreach ($smartphones as $item) {
            $brandName = $this->parseData($item->smartphone->getUri());
            $gambar = $this->parseData($item->gambar->getValue());
            $seri = $this->parseData($item->seri->getValue());
            $data[] = [
                'name' => $brandName,
                'gambar' => $gambar,
                'seri' => $seri,
            ];
        }

        return response()->json([
            'data'=>$data, 
            'query'=>$query
        ]);
    }

}