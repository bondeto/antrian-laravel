// Queue to manage multiple calls sequentially
const callQueue = [];
let isProcessing = false;

export function useQueueVoice() {
    const playAudio = (path) => {
        return new Promise((resolve, reject) => {
            const audio = new Audio(path);
            audio.onended = () => resolve();
            audio.onerror = (e) => reject(e);
            audio.play().catch(err => reject(err));
        });
    };

    const getNumberSequence = (num) => {
        const sequence = [];
        if (num === 0) return ['0'];

        if (num === 100) return ['seratus'];
        if (num === 1000) return ['seribu'];

        const hundreds = Math.floor(num / 100);
        const remainder100 = num % 100;

        if (hundreds > 0) {
            if (hundreds === 1) {
                sequence.push('seratus');
            } else {
                sequence.push(hundreds.toString());
                sequence.push('ratus');
            }
        }

        if (remainder100 > 0) {
            if (remainder100 < 12) {
                if (remainder100 === 10) sequence.push('10');
                else if (remainder100 === 11) sequence.push('11');
                else sequence.push(remainder100.toString());
            } else if (remainder100 < 20) {
                sequence.push((remainder100 % 10).toString());
                sequence.push('belas');
            } else {
                const tens = Math.floor(remainder100 / 10);
                const ones = remainder100 % 10;
                sequence.push(tens.toString());
                sequence.push('puluh');
                if (ones > 0) sequence.push(ones.toString());
            }
        }

        return sequence;
    };

    const processQueue = async () => {
        if (isProcessing || callQueue.length === 0) return;

        isProcessing = true;
        const queueData = callQueue.shift();
        console.log('[QueueVoice] Starting call sequence for:', queueData.full_number);

        try {
            const basePath = window.location.origin + '/bandara';
            const audioList = [];

            // 1. Ding dong / Initial sound (Optional if you have one, currently just nomor-antrean)

            // 2. Nomor Antrean
            audioList.push(`${basePath}/frasa/nomor-antrean.wav`);

            // 3. Huruf (Layanan)
            const prefix = queueData.full_number.split('-')[0].toUpperCase();
            audioList.push(`${basePath}/huruf/${prefix}.wav`);

            // 4. Angka Nomor
            const num = parseInt(queueData.number);
            const numSeq = getNumberSequence(num);
            numSeq.forEach(s => {
                if (['seratus', 'seribu', 'ratus', 'ribu', 'puluh', 'belas'].includes(s)) {
                    audioList.push(`${basePath}/satuan/${s}.wav`);
                } else {
                    audioList.push(`${basePath}/angka/${s}.wav`);
                }
            });

            // 5. Silakan Menuju
            audioList.push(`${basePath}/frasa/silakan-menuju.wav`);

            // 6. Loket / Meja
            audioList.push(`${basePath}/frasa/loket.wav`);

            // 7. Nomor Loket
            const counterName = queueData.counter?.name || '';
            const counterMatch = counterName.match(/\d+/);
            if (counterMatch) {
                const counterNum = parseInt(counterMatch[0]);
                const counterSeq = getNumberSequence(counterNum);
                counterSeq.forEach(s => {
                    if (['seratus', 'seribu', 'ratus', 'ribu', 'puluh', 'belas'].includes(s)) {
                        audioList.push(`${basePath}/satuan/${s}.wav`);
                    } else {
                        audioList.push(`${basePath}/angka/${s}.wav`);
                    }
                });
            }

            // Play one by one
            for (const audioPath of audioList) {
                await playAudio(audioPath);
            }
            console.log('[QueueVoice] Sequence completed.');

            // Pause slightly between different calls
            await new Promise(resolve => setTimeout(resolve, 1500));

        } catch (error) {
            console.error('[QueueVoice] Error playing sequence:', error);
        } finally {
            isProcessing = false;
            // Move to next in queue
            processQueue();
        }
    };

    const playQueueCall = (queue) => {
        callQueue.push(queue);
        processQueue();
    };

    return { playQueueCall };
}
