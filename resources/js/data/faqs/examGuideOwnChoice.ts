// resources/js/data/faqs/examGuideOwnChoice.ts

import type { FaqPage } from '@/lib/faqs'
import { ROCK_POP_ACOUSTIC_RULE } from '@/lib/guitarExams'
import { OWN_CHOICE_PATH } from '@/data/ownChoiceSongs'

// The FAQ on /exam-guide/own-choice-songs. The page draws its accordion from
// this list and site search reads it too, so an answer is written here once.
// Lengths, tempos and grade parameters live in data/ownChoiceSongs.ts and are
// not repeated here: answers point at the tables instead.
const page: FaqPage = {
  path: OWN_CHOICE_PATH,
  label: 'Own-Choice Songs FAQ',
  items: [
    {
      question: 'Which song in a Rock & Pop exam can be an own choice?',
      answer:
        'Only song 2. Song 1 comes from the Trinity book for the grade, and song 3 must be one of the book’s three Technical Focus songs, both played to the official backing track.',
      keywords: ['own choice', 'song 2', 'second song', 'rock and pop', 'which song'],
    },
    {
      question: 'Does Trinity need to approve an own-choice song first?',
      answer:
        'No, and it can’t: Trinity says it cannot pre-approve own-choice songs. Check the song against the parameters for your instrument and grade on this page. If it looks and feels like the songs in the book, it is probably right.',
      keywords: ['approve', 'approval', 'pre-approve', 'allowed', 'check', 'permission'],
    },
    {
      question: 'Are there prerequisites for a Rock & Pop grade?',
      answer:
        'No. Trinity needs no earlier grade and no theory exam before any Rock & Pop grade. What each grade does set is the standard of the music, and for an own-choice song that standard is in the parameter tables on this page.',
      keywords: ['prerequisites', 'requirements', 'previous grade', 'skip a grade', 'theory', 'entry requirements'],
    },
    {
      question: 'How hard should an own-choice song be?',
      answer:
        'The same standard as the songs in the Trinity book for the grade. Compare it with a book song on rhythm, range, intervals, chords, phrasing, dynamics, tempo and length. The parameters are cumulative, and a song does not need to contain everything on the list.',
      keywords: ['difficulty', 'standard', 'level', 'too easy', 'too hard', 'parameters', 'equivalent'],
    },
    {
      question: 'What happens if an own-choice song is too long?',
      answer:
        'The examiner stops it when it reaches the maximum length for the grade, and may stop it earlier once they have heard enough. You can shorten a song by cutting an intro or outro, or dropping a verse.',
      keywords: ['length', 'how long', 'duration', 'minutes', 'cut', 'shorten', 'too long'],
    },
    {
      question: 'Can I play an own-choice song to a backing track?',
      answer:
        'Yes, as long as the track leaves out your part, and a home-made one is fine. For a face-to-face exam bring it as an MP3 on a USB stick or CD. For a digital exam make sure you and the track are both clearly heard. You can also play it unaccompanied.',
      keywords: ['backing track', 'mp3', 'usb', 'cd', 'karaoke', 'unaccompanied', 'own track'],
    },
    {
      question: 'Can my teacher accompany my own-choice song?',
      answer:
        'Yes. One live musician can play or sing along with song 2, and that can be your teacher. In a face-to-face exam they come into the room for song 2 only.',
      keywords: ['teacher', 'accompanist', 'accompany', 'live', 'piano', 'another musician'],
    },
    {
      question: 'Can I sing while I play my own-choice song?',
      answer:
        'Yes. Guitar, bass, drums and keyboards candidates can add live vocals to song 2. Singers can accompany themselves on any instrument.',
      keywords: ['sing', 'vocals', 'singing and playing', 'self accompany'],
    },
    {
      question: 'What music do I give the examiner for an own-choice song?',
      answer:
        'A lead sheet (lyrics, chords and melody line), a chord chart with lyrics, a full score in staff notation, or staff notation with TAB. Face-to-face: bring an original copy (or an authorised download with proof of purchase) plus a copy for the examiner with the candidate’s name and number on it, which they keep. Digital: upload a scan or photo of the song, all its pages in one file, and have the copy you play from in shot while you film.',
      keywords: ['sheet music', 'copy', 'chord chart', 'lead sheet', 'tab', 'tablature', 'upload', 'notation', 'photocopy'],
    },
    {
      question: 'Can I use an older Trinity song or one of Trinity’s extra songs?',
      answer:
        'Yes. Songs from the 2015–2017 Rock &amp; Pop syllabus can be an own choice if they meet today’s parameters, and Trinity publishes extra arrangements for song 2 at <a href="https://www.trinityrock.com/extra-songs" target="_blank" rel="noopener noreferrer">trinityrock.com/extra-songs</a>.',
      keywords: ['old syllabus', '2015', '2017', 'extra songs', 'additional arrangements', 'downloads'],
    },
    {
      question: 'Can I play a song I wrote myself?',
      answer:
        'Yes. An original song counts as song 2, and you own the copyright of songs you write yourself. It still needs to meet the parameters for the grade.',
      keywords: ['own song', 'original', 'composition', 'wrote', 'write my own', 'compose'],
    },
    {
      question: 'Can I use a piece from another syllabus as an own choice?',
      answer:
        'It can work, for example a Classical &amp; Jazz or musical theatre piece, but don’t assume it meets the Rock &amp; Pop parameters. Check it against the table for your instrument and grade.',
      keywords: ['classical', 'musical theatre', 'another syllabus', 'other exam board'],
    },
    {
      question: 'Can a singer choose an own-choice song in another language?',
      answer:
        'Yes, for song 2 only. Bring a copy in the original language and an English translation of the lyrics for the examiner.',
      keywords: ['language', 'translation', 'foreign', 'welsh', 'spanish', 'french'],
    },
    {
      question: 'Can I play my own-choice song on an acoustic guitar?',
      answer: `${ROCK_POP_ACOUSTIC_RULE}`,
      keywords: ['acoustic', 'acoustic guitar', 'electro-acoustic', 'electric'],
    },
    {
      question: 'Do I get extra marks for an own-choice song or for playing from memory?',
      answer:
        'No. Song 2 is worth the same 25 marks whether it comes from the book or is your own choice, and playing from memory is allowed but earns no extra marks.',
      keywords: ['marks', 'extra marks', 'memory', 'memorise', 'bonus'],
    },
  ],
}

export default page
