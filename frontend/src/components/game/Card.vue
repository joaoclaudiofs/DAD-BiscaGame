<script setup>
import { computed } from 'vue';

const props = defineProps({
    deck: String,
    card: Object,
    faceDown: Boolean
})

//inicial do naipe em português + valor da carta
//1:A, 11:J, 12:Q, 13:K
const imgPath = computed(() => {
    let path = "";

    switch (props.card.suit) {
        case "hearts":
            path += "c";
            break;
        case "diamonds":
            path += "o";
            break;
        case "spades":
            path += "e";
            break;
        case "clubs":
            path += "p";
    }

    switch (props.card.face) {
        case "A":
            path += "1";
            break;
        case "J":
            path += "11";
            break;
        case "Q":
            path += "12";
            break;
        case "K":
            path += "13";
            break;
        default:
            path += props.card.face;
    }

    return path;
});

const backImage = computed(() =>
  new URL(`../../assets/cards/${props.deck}/semFace.png`, import.meta.url).href
);

const getImage = computed(() =>
    new URL(`../../assets/cards/${props.deck}/${imgPath.value}.png`, import.meta.url).href
);

const emit = defineEmits(['playCard'])
function handleClick() {    
    emit('playCard', props.card);
}
</script>

<template>
    <div class="relative w-16 h-23 cursor-pointer overflow-hidden bg-card hover:shadow-md transition-all duration-200 hover:scale-105"
        @click="handleClick">
        <!--Verso-->
        <div v-if="faceDown" class="w-full h-full">
            <img :src="backImage" alt="Verso da carta" class="w-full h-full object-cover" />
        </div>

        <!--Frente-->
        <div v-else class="w-full h-full">
            <img :src="getImage" :alt="'Carta ' + (props.card?.face || 'Unknown')" class="w-full h-full object-cover" />
        </div>
    </div>
</template>