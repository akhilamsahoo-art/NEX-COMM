<div x-data="{ 
    state: $wire.$entangle('{{ $getStatePath() }}'),
    async openCloudinary() {
        if (typeof window.cloudinary === 'undefined') {
            const script = document.createElement('script');
            script.src = 'https://media-library.cloudinary.com/global/all.js';
            script.type = 'text/javascript';
            document.head.appendChild(script);
            
            // Wait a moment for script to load
            await new Promise(resolve => setTimeout(resolve, 1000));
        }
        
        window.cloudinary.openMediaLibrary({
            cloud_name: 'dvpmqwwrm',
            api_key: '655948633129366',
            insert_handler: (data) => {
                this.state = data.assets[0].secure_url;
                $wire.set('{{ $getStatePath() }}', data.assets[0].secure_url);
            }
        }, { 
            multiple: false 
        });
    }
}" class="w-full p-4 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50">
    <div class="flex flex-col items-center justify-center gap-4">
        
        <button 
            type="button"
            x-on:click="openCloudinary()"
            class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-lg shadow-lg flex items-center transition-all active:scale-95"
            style="background-color: #2563eb !important; color: white !important; cursor: pointer !important; z-index: 999;"
        >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            BROWSE CLOUDINARY
        </button>

        <template x-if="state">
            <div class="relative mt-2 w-40 h-40 border rounded overflow-hidden shadow-sm bg-white">
                <img :src="state" class="w-full h-full object-cover">
                <button type="button" x-on:click="state = null" class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs">✕</button>
            </div>
        </template>
        
        <template x-if="!state">
            <p class="text-xs text-gray-500 italic">No image selected from Cloudinary yet.</p>
        </template>
    </div>
</div>