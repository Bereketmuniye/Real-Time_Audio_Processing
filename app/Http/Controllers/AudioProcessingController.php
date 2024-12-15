<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // Ensure this is included
use Illuminate\Support\Facades\Storage;
use Google\Cloud\Speech\V1\SpeechClient;

class AudioProcessingController extends Controller
{
    public function processAudio(Request $request)
    {
        $request->validate([
            'audio' => 'required|file|mimes:wav,mp3,aac,webm',
        ]);

        try {
            if ($request->hasFile('audio')) {
                Log::info('Audio file received.');
            } else {
                Log::error('No audio file found in the request.');
                return response()->json(['success' => false, 'message' => 'No audio file found.']);
            }

            $audio = $request->file('audio');
            $path = $audio->storeAs('audios', 'recording.wav', 'public');
            Log::info('Audio file stored at: ' . $path);

            // Use Google Cloud Speech-to-Text
            $speech = new SpeechClient();
            $audioContent = file_get_contents(storage_path('app/public/audios/recording.wav'));

            $response = $speech->recognize([
                'audio' => [
                    'content' => $audioContent,
                ],
                'config' => [
                    'encoding' => AudioEncoding::LINEAR16, // Adjust based on the actual encoding
                    'sample_rate_hertz' => 16000, // Adjust based on your audio
                    'language_code' => 'en-US',
                ],
            ]);

            $transcription = '';
            foreach ($response->getResults() as $result) {
                $transcription .= $result->getAlternatives()[0]->getTranscript();
            }

            return response()->json([
                'success' => true,
                'transcription' => $transcription
            ]);
        } catch (\Exception $e) {
            Log::error('Error processing audio: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error processing audio: ' . $e->getMessage()]);
        }
    }
}