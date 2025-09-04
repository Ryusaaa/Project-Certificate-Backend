<?php

namespace App\Http\Controllers\Instruktur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Instruktur;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class InstrukturManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = Instruktur::with(['merchant']);

        // SEARCH - Case insensitive search on instructor name and email
        if ($search = $request->input('search')) {
            $searchLower = strtolower($search);
            $query->where(function ($q) use ($searchLower) {
                $q->whereRaw("LOWER(name) like ?", ["%{$searchLower}%"])
                  ->orWhereRaw("LOWER(email) like ?", ["%{$searchLower}%"]);
            });
        }

        // SORT (default: by name asc)
        $sortKey = $request->input('sortKey', 'name');
        $sortOrder = $request->input('sortOrder', 'asc');
        $query->orderBy($sortKey, $sortOrder);

        // PAGINATION
        $perPage = $request->input('perPage', 10);
        $instrukturs = $query->paginate($perPage);

        // Format response
        $result = $instrukturs->getCollection()->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'email' => $item->email,
                'role_id' => $item->role_id,
                'merchant_id' => $item->merchant_id,
                'merchant' => $item->merchant,
                'signature' => $item->signature ?? null,
                'has_signature' => !empty($item->signature),
            ];
        });

        return response()->json([
            'total' => $instrukturs->total(),
            'current_page' => $instrukturs->currentPage(),
            'last_page' => $instrukturs->lastPage(),
            'per_page' => $instrukturs->perPage(),
            'message' => 'Data instructors fetched successfully.',
            'data' => $result,
        ]);
    }

    /**
     * Attempt to parse a multipart/form-data PUT body for a single file field and store it.
     * Returns stored path on success or null.
     */
    private function parseMultipartPutForField($fieldName)
    {
        try {
            $raw = file_get_contents('php://input');
            if (empty($raw)) return null;

            // Try to extract boundary
            if (!preg_match('/boundary=(.*)$/', $_SERVER['CONTENT_TYPE'] ?? '', $matches)) {
                return null;
            }
            $boundary = trim($matches[1], '"');
            $parts = preg_split('/-+' . preg_quote($boundary, '/') . '/', $raw);
            foreach ($parts as $part) {
                if (stripos($part, "name=\"$fieldName\"") !== false) {
                    // extract filename
                    if (preg_match('/filename="(.*?)"/i', $part, $m)) {
                        $filename = $m[1];
                    } else {
                        $filename = uniqid($fieldName . '_');
                    }
                    // extract body after double CRLF
                    $seg = preg_split("/\r\n\r\n/", $part, 2);
                    if (count($seg) < 2) continue;
                    $body = $seg[1];
                    // strip trailing CRLF--
                    $body = preg_replace('/\r\n$/', '', $body);
                    // store file
                    $ext = pathinfo($filename, PATHINFO_EXTENSION) ?: 'bin';
                    $storeName = 'signatures/' . uniqid('sig_') . '.' . $ext;
                    Storage::disk('public')->put($storeName, $body);
                    return $storeName;
                }
            }
            return null;
        } catch (\Exception $e) {
            Log::error('Error parsing multipart PUT body', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function show($id)
    {
        $instruktur = Instruktur::findOrFail($id);

        if (!$instruktur) {
            return response()->json(['message' => 'Instruktur not found'], 404);
        }

        return response()->json($instruktur, 200);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:instrukturs,email',
                'password' => 'required|string|min:6',
                // signature upload is optional
                'signature' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            // Get merchant_id dari user yang sedang login
            $merchant_id = auth('sanctum')->user()->merchant_id ?? 1;

            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role_id' => '2',
                'merchant_id' => $merchant_id,
            ];

            // handle optional signature file
            if ($request->hasFile('signature')) {
                $path = $request->file('signature')->store('signatures', 'public');
                $data['signature'] = $path;
            } elseif ($request->filled('signature')) {
                $data['signature'] = $request->signature;
            }

            $instruktur = Instruktur::create($data);

            return response()->json($instruktur, 201);

        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the instructor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $instruktur = Instruktur::findOrFail($id);
            // If request is PUT and multipart but PHP didn't parse files, try to parse signature from raw input
            $putParsedSignaturePath = null;
            $contentType = $request->header('Content-Type') ?? '';
            if (strtoupper($request->method()) === 'PUT' && strpos($contentType, 'multipart/form-data') !== false && !$request->hasFile('signature')) {
                $putParsedSignaturePath = $this->parseMultipartPutForField('signature');
                if ($putParsedSignaturePath) {
                    // make parsed path available to the request handling below
                    $request->merge(['_parsed_signature_path' => $putParsedSignaturePath]);
                }
            }
            // Debug info to help diagnose file upload issues with PUT
            Log::info('Instruktur update request', [
                'method' => $request->method(),
                'content_type' => $request->header('Content-Type'),
                'hasFile_signature' => $request->hasFile('signature'),
                'file_keys' => array_keys($request->files->all())
            ]);

            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:instrukturs,email,' . $instruktur->id,
                'password' => 'sometimes|required|string|min:6',
                'signature' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            $data = $request->only(['name', 'email']);

            if ($request->filled('password')) {
                $data['password'] = Hash::make($request->password);
            }

            // handle signature upload
            $signatureAction = null; // 'added' or 'replaced' or null
            if ($request->hasFile('signature')) {
                $existing = !empty($instruktur->signature) && Storage::disk('public')->exists($instruktur->signature);
                if ($existing) {
                    // replace: delete old
                    Storage::disk('public')->delete($instruktur->signature);
                    $signatureAction = 'replaced';
                } else {
                    $signatureAction = 'added';
                }
                $path = $request->file('signature')->store('signatures', 'public');
                $data['signature'] = $path;
            } elseif ($request->filled('signature')) {
                $sig = $request->input('signature');
                // If signature is a data URI (base64), decode and store
                if (strpos($sig, 'data:image') === 0) {
                    try {
                        // parse data uri
                        preg_match('/^data:image\/(\w+);base64,/', $sig, $matches);
                        $ext = $matches[1] ?? 'png';
                        $base64 = preg_replace('#^data:image/\w+;base64,#i', '', $sig);
                        $decoded = base64_decode($base64);
                        if ($decoded === false) throw new \Exception('Base64 decode failed');
                        $filename = 'signatures/' . uniqid('sig_') . '.' . $ext;
                        Storage::disk('public')->put($filename, $decoded);

                        // delete old
                        if (!empty($instruktur->signature) && Storage::disk('public')->exists($instruktur->signature)) {
                            Storage::disk('public')->delete($instruktur->signature);
                        }

                        $data['signature'] = $filename;
                        // if old existed then action is replaced, else added
                        $signatureAction = (!empty($instruktur->signature) && Storage::disk('public')->exists($instruktur->signature)) ? 'replaced' : 'added';
                    } catch (\Exception $e) {
                        Log::error('Failed to save signature data URI', ['error' => $e->getMessage()]);
                    }
                } else {
                    // treat as path or url string
                    $data['signature'] = $sig;
                }
            }

            // if we parsed a signature from PUT raw body, use it
            if (!$request->hasFile('signature') && $putParsedSignaturePath) {
                $existing = !empty($instruktur->signature) && Storage::disk('public')->exists($instruktur->signature);
                $signatureAction = $existing ? 'replaced' : 'added';
                // delete old only if replacing
                if ($existing) {
                    Storage::disk('public')->delete($instruktur->signature);
                }
                $data['signature'] = $putParsedSignaturePath;
            }

            $instruktur->update($data);

            $response = $instruktur->toArray();
            if ($signatureAction) $response['signature_action'] = $signatureAction;

            return response()->json($response, 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the instructor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $instruktur = Instruktur::findOrFail($id);

        if (!$instruktur) {
            return response()->json(['message' => 'Instruktur not found'], 404);
        }

        $instruktur->delete();

        return response()->json(['message' => 'Instruktur deleted successfully'], 200);
    }
}
