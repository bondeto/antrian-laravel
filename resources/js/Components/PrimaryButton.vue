<template>
    <button
        class="w-full inline-flex justify-center items-center rounded-xl border border-transparent bg-gradient-to-r from-blue-600 to-indigo-600 px-5 py-3 text-sm font-bold uppercase tracking-wide text-white shadow-lg transition duration-200 ease-in-out hover:from-blue-700 hover:to-indigo-700 hover:shadow-blue-500/30 focus:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed"
    >
        <slot />
    </button>
</template>
