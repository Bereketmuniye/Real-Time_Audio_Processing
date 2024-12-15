<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-Time Audio Processing</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2>Real-Time Audio Transcription</h2>
        <p>Record your audio and get the transcription in real-time.</p>

        <div class="mt-4">
            <button id="start-recording" class="btn btn-primary">Start Recording</button>
            <button id="stop-recording" class="btn btn-danger" disabled>Stop Recording</button>
        </div>

        <audio id="audio-preview" controls class="mt-3 d-none"></audio>

        <form id="transcription-form" class="mt-4">
            <button type="submit" class="btn btn-success" disabled>Submit for Transcription</button>
        </form>

        <div id="transcription-result" class="mt-4"></div>
    </div>

    <script>
        let mediaRecorder;
        let audioChunks = [];
        let audioBlob;

        const startRecordingButton = document.getElementById('start-recording');
        const stopRecordingButton = document.getElementById('stop-recording');
        const audioPreview = document.getElementById('audio-preview');
        const transcriptionForm = document.getElementById('transcription-form');
        const transcriptionResult = document.getElementById('transcription-result');

        // Start recording event
        startRecordingButton.addEventListener('click', async () => {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);

            mediaRecorder.ondataavailable = (event) => {
                audioChunks.push(event.data);
            };

            mediaRecorder.onstop = async () => {
                audioBlob = new Blob(audioChunks, { type: 'audio/webm' }); // Adjust MIME type if needed
                const audioUrl = URL.createObjectURL(audioBlob);
                audioPreview.src = audioUrl;
                audioPreview.classList.remove('d-none');

                // Enable the transcription button after recording
                transcriptionForm.querySelector('button').disabled = false;

                // Reset audio chunks for the next recording
                audioChunks = [];
            };

            mediaRecorder.start();
            startRecordingButton.disabled = true;
            stopRecordingButton.disabled = false;
        });

        // Stop recording event
        stopRecordingButton.addEventListener('click', () => {
            mediaRecorder.stop();
            startRecordingButton.disabled = false;
            stopRecordingButton.disabled = true;
        });

        // Form submission for audio transcription
        transcriptionForm.addEventListener('submit', (e) => {
            e.preventDefault();

            // Create FormData to submit the audio blob
            const formData = new FormData();
            formData.append('audio', audioBlob, 'recording.wav');

            // Submit the audio file to the server using fetch
            fetch('{{ route('process.audio') }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}', // Ensure CSRF token is included
                },
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    transcriptionResult.innerHTML = `<p><strong>Transcription:</strong> ${data.transcription}</p>`;
                } else {
                    transcriptionResult.innerHTML = `<p>Error processing audio: ${data.message}</p>`;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                transcriptionResult.innerHTML = `<p>Error processing audio: ${error.message}</p>`;
            });
        });
    </script>
</body>
</html>