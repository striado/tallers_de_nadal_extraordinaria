<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Usuari;
use App\Models\Taller;
use Illuminate\Support\Facades\Storage;

class AlumnesController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $usuaris = Usuari::all();
        return view('alumnes', compact('usuaris'));
    }

    /**
     * Actualitzar dades de les persones
     */
    public function actualitzar()
    {
        // Llegim el fitxer
        $fitxer = Storage::disk('local')->get('llista.txt');
        dd($fitxer);
        $linies = explode(PHP_EOL, $fitxer);
        $linies_alumnes = array();
        // Netejem espais i salts de linia
        foreach ($linies as $linia) {
            $paraules = explode(" ", $linia);
            if (filter_var($paraules[0], FILTER_VALIDATE_EMAIL)) {
                $array_netejada = array_filter($paraules);
                array_push($linies_alumnes, $array_netejada);
            }
        }
        
        // Construim una array associativa perque sigui més facil asignar camps
        $info_alumnes = array();
        foreach ($linies_alumnes as $linia) {
            $alumne = array();
            $linia = array_values($linia);
            foreach ($linia as $clau => $valor) {
                if ($clau == '0') {
                    array_push($alumne, $alumne['email'] = $valor);
                } elseif ($clau == '1') {
                    array_push($alumne, $alumne['curs'] = $valor);
                } elseif ($clau == count($linia) - 1) {
                    array_push($alumne, $alumne['nom'] = $valor);
                } else {
                    array_push($alumne, $alumne['cognoms'] = $valor . ($alumne['cognoms'] ?? ''));
                }
            }
            $alumne['cognoms'] = array_filter(array_reverse(explode(',', $alumne['cognoms'])));
            $alumne['cognoms'] = implode(' ', $alumne['cognoms']);
            $alumne['curs'] = str_replace('OU=', '', $alumne['curs']);
            $array = preg_split('/([\d]+)/', $alumne['curs'], -1, PREG_SPLIT_DELIM_CAPTURE);
            $alumne['curs'] = $array[1];
            $alumne['etapa'] = $array[0];
            $alumne['grup'] = $array[2];
            array_push($info_alumnes, $alumne);
        }
        
        // Comprovem per cada usuari si aquest existeix a la BBDD, si existeix sobreescrivim, si no fem un nou
        foreach ($info_alumnes as $alumne) {
            $usuari = Usuari::where('email', $alumne['email'])->first();
            if ($usuari) {
                $usuari->email = $alumne['email'];
                $usuari->nom = $alumne['nom'];
                $usuari->cognoms = $alumne['cognoms'];
                $usuari->etapa = $alumne['etapa'];
                $usuari->curs = $alumne['curs'];
                $usuari->grup = $alumne['grup'];
                $usuari->categoria = (strpos(explode('@', $alumne['email'])[0], '.')) ? 'alumne' : 'professor';

                try {
                    $usuari->save();
                } catch (\Throwable $th) {
                    return redirect()->back()->with('error', 'Hi ha hagut un problema amb la actualització de les dades.');
                }
            } else {
                $persona_nova = new Usuari;
                $persona_nova->email = $alumne['email'];
                $persona_nova->nom = $alumne['nom'];
                $persona_nova->cognoms = $alumne['cognoms'];
                $persona_nova->etapa = $alumne['etapa'];
                $persona_nova->curs = $alumne['curs'];
                $persona_nova->grup = $alumne['grup'];
                $persona_nova->categoria = (strpos(explode('@', $alumne['email'])[0], '.')) ? 'alumne' : 'professor';
                $persona_nova->admin = 0;
                $persona_nova->superadmin = 0;

                try {
                    $persona_nova->save();
                } catch (\Throwable $th) {
                    return redirect()->back()->with('error', 'Hi ha hagut un problema amb la actualització de les dades.');
                }
            }
        }
        // Si fins aquí no ha petat vol dir que ha anat bé 
        return redirect()->back()->with('success', 'Dades carregades correctament.');
    }

    // Mostrar formulari per afegir un alumne
    public function afegirAlumne(){
        $combobox = array(
            'ESO' => array(
                '1' => array(
                    'A','B','C','D','E'
                ),
                '2' => array(
                    'A','B','C','D','E'
                ),
                '3' => array(
                    'A','B','C','D','E'
                ),
                '4' => array(
                    'A','B','C','D','E'
                )
            ),
            'BAT' => array(
                '1' => array('A','B'),
                '2' => array('A','B')
            ),
            'SMX' => array(
                '1' => array(
                    'A','B','C','D','E'
                ),                
            ),
            'FPB' => array(
                '1' => array('A'),
                '2' => array('A'),
            ) 
        );
        return view('noualumne', compact('combobox'));
    }

    // Crear el nou alumne
    public function createAlumne(Request $request){
        $request->validate(
            [
                'nom' => 'required',
                'cognoms' => 'required',
                'curs' => 'required|not_in:0',
            ],
            [
                'nom.required' => 'El camp nom és obligatori.',
                'cognoms.required' => 'El camp cognoms és obligatori.',
                'curs.required' => 'El camp curs és obligatori.',
                'curs.not_in' => 'El camp curs és obligatori.'
            ]
        );

        $cursArr = explode("-",$request->curs);
        $etapa = $cursArr[0];
        $curs = $cursArr[1];
        $grup = $cursArr[2];

        $usuari = new Usuari;
        $usuari->nom = $request->nom;
        $usuari->cognoms = $request->cognoms;
        $usuari->etapa = $etapa;
        $usuari->curs = $curs;
        $usuari->grup = $grup;

        try {
            $usuari->save();
            $success = true;
        } catch (\Throwable $th) {
            $success = false;
        }
        
        if ($success) {
            return redirect(route('afegir_alumnes'))->with('success', 'L\'usuari s\'ha creat correctament.');
        } else {
            return redirect(route('afegir_alumnes'))->with('error', 'No s\'ha pogut creat l\'usuari.');
        }
    }

    public function apuntarAlumne($id){
        $alumne = Usuari::find($id);
        $tallers = Taller::all();
        return view('tallers.apuntar_alumne', compact('alumne', 'tallers'));
    }

    public function apuntarTallers(Request $request){
        $request->validate(
            [
                'taller1' => 'required|not_in:0,' . $request->taller2 . ','. $request->taller3,
                'taller2' => 'required|not_in:0,' . $request->taller1 . ','. $request->taller3,
                'taller3' => 'required|not_in:0,' . $request->taller1 . ','. $request->taller2,
            ],
            [
                'taller1.required' => 'El camp Taller 1 és obligatori.',
                'taller1.not_in' => 'El camp Taller 1 és obligatori y no es pot repetir.',
                'taller2.required' => 'El camp Taller 2 és obligatori.',
                'taller2.not_in' => 'El camp Taller 2 és obligatori y no es pot repetir.',
                'taller3.required' => 'El camp Taller 3 és obligatori.',
                'taller3.not_in' => 'El camp Taller 3 és obligatori y no es pot repetir.'
            ]
        );

        $taller1 = Taller::find($request->taller1);
        $taller2 = Taller::find($request->taller2);
        $taller3 = Taller::find($request->taller3);

        $usuari = Usuari::find($request->id_alumne);

        try {
            $usuari->tallers_primera_opcio()->attach($taller1, ['prioritat' => 1]);
            $usuari->tallers_segona_opcio()->attach($taller2, ['prioritat' => 2]);
            $usuari->tallers_tercera_opcio()->attach($taller3, ['prioritat' => 3]);
            $success = true;
        } catch (\Throwable $th) {
            $success = false;
        }

        if ($success) {
            return redirect()->back()->with('success', 'Has apuntat correctament l\'alumne al taller.');
        } else {
            return redirect()->back()->with('error', 'Hi ha hagut un problema i no has pogut apuntar l\'alumne al taller.');
        }
    }
}
