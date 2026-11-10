// resources/js/data/ownChoiceSongs.ts

// Trinity Rock & Pop own-choice songs, written once. The Own-choice songs page
// draws every table and card from here. Sources, all in ~/Documents/Claude:
// the five "R&P <instrument> Syllabus.pdf" files (graded exams from 2018,
// "Parameters for own-choice songs"), "Digital Grades - Rock & Pop
// Syllabus.pdf" (from November 2020), plus Trinity's support article
// "Music Digital Requirements - Rock and Pop Exams" ("cannot pre-approve") and
// the Trinity blog "Make it yours" (23 Aug 2023, JJ Wheeler).
// Time signatures are drawn as symbols in the PDFs and were read off the page:
// Initial 4/4, 2/2, 3/4; Grade 2 adds 2/4; Grade 3 adds 6/8, 12/8.

import { ROCK_POP_ACOUSTIC_RULE } from '@/lib/guitarExams'
import { formatGrade } from '@/lib/grades'

export const OWN_CHOICE_PATH = '/exam-guide/own-choice-songs'

// Labels come from the shared formatter, so this is not a second hand-typed
// grade list (SyllabusPickerGuardTest).
export const OWN_CHOICE_GRADES: string[] = ['Initial', 1, 2, 3, 4, 5, 6, 7, 8].map(formatGrade)

// Length and tempo are the same on all five instruments, so they live here
// and not in each instrument's rows. One entry per grade, in grade order.
const FULL_TEMPO = 'Full range of tempos, tempo changes within songs'

const LENGTH_AND_TEMPO: [string, string][] = [
  ['45 seconds – 2 minutes', '60–130 bpm'],
  ['1 – 2.5 minutes', '60–160 bpm'],
  ['1.5 – 3 minutes', '50–160 bpm'],
  ['1.5 – 3.5 minutes', FULL_TEMPO],
  ['2.5 – 4 minutes', FULL_TEMPO],
  ['2.5 – 4 minutes', FULL_TEMPO],
  ['3 – 4.5 minutes', FULL_TEMPO],
  ['3 – 4.5 minutes', FULL_TEMPO],
  ['3.5 – 5 minutes', FULL_TEMPO],
]

export const GRADE_OVERVIEW: { grade: string; length: string; tempo: string }[] = OWN_CHOICE_GRADES.map((grade, index) => ({
  grade,
  length: LENGTH_AND_TEMPO[index][0],
  tempo: LENGTH_AND_TEMPO[index][1],
}))

export interface ParameterRow {
  label: string
  value: string
}

export interface OwnChoiceInstrument {
  key: 'guitar' | 'bass' | 'drums' | 'keyboards' | 'vocals'
  name: string
  // Instrument-only points from the syllabus, beyond the parameter tables.
  notes: string[]
  // One list of rows per grade, in OWN_CHOICE_GRADES order. Only what the
  // syllabus states for that grade; everything carries up from below.
  grades: ParameterRow[][]
}

const NONE = 'None'
const DYN_INITIAL = 'mp, mf, f — few changes, simple contrasts only'
const DYN_G1 = 'p, mp, mf, f with occasional hairpins'
const TS_INITIAL = '4/4, 2/2, 3/4'
const TS_G2 = 'Adds 2/4; occasional changes of time signature'
const TS_G3 = 'Adds 6/8 and 12/8'
const TS_G4 = 'All regular time signatures'
const OTHER = 'Other directions / techniques'

export const OWN_CHOICE_INSTRUMENTS: OwnChoiceInstrument[] = [
  {
    key: 'guitar',
    name: 'Guitar',
    notes: [ROCK_POP_ACOUSTIC_RULE],
    grades: [
      [
        { label: 'Rhythmic values', value: 'Mainly crotchets and quavers, plus minims, dotted minims and semibreves in melodic passages, equivalent rests, ties' },
        { label: 'Syncopation', value: 'Simple syncopation' },
        { label: 'Time signatures', value: TS_INITIAL },
        { label: 'Dynamics', value: DYN_INITIAL },
        { label: 'Range / position', value: 'Basic hand positions' },
        { label: 'Keys and chords', value: 'Simple keys and chords, two-note power chords' },
        { label: 'Part writing', value: 'Mainly small chords of two to four notes, simple full chords, time to change between chords, occasional very straightforward melodic writing' },
        { label: 'Improvisation', value: NONE },
      ],
      [
        { label: 'Rhythmic values', value: 'As Initial, with more variety' },
        { label: 'Syncopation', value: 'As Initial' },
        { label: 'Time signatures', value: 'As Initial' },
        { label: 'Dynamics', value: DYN_G1 },
        { label: 'Range / position', value: 'Occasionally moving beyond basic positions, but with enough time to move easily to any unusual positions' },
        { label: 'Keys and chords', value: 'As Initial' },
        { label: 'Part writing', value: 'Still mainly chordal but more use of full chords, some melodic sections' },
        { label: OTHER, value: 'Staccato, accent, pause on last note' },
        { label: 'Improvisation', value: NONE },
      ],
      [
        { label: 'Rhythmic values', value: 'Occasional semiquavers' },
        { label: 'Syncopation', value: 'Simple syncopation' },
        { label: 'Time signatures', value: TS_G2 },
        { label: 'Dynamics', value: 'Crescendos/decrescendos within phrases and greater use of contrasts' },
        { label: 'Range / position', value: 'Slightly more varied than Grade 1, still leaving time for any more challenging positions' },
        { label: 'Keys and chords', value: 'Wider range of keys and chords' },
        { label: 'Part writing', value: 'Riffs and written solos becoming more important alongside chordal sections' },
        { label: OTHER, value: 'RH damp, palm mute, ring, slides, occasional use of hammer-on and pull-off, optional distortion/overdrive/crunch' },
        { label: 'Improvisation', value: NONE },
      ],
      [
        { label: 'Rhythmic values', value: 'Semiquavers more frequent in accompaniment and melodic material' },
        { label: 'Syncopation', value: 'Mainly simple syncopation' },
        { label: 'Time signatures', value: TS_G3 },
        { label: 'Dynamics', value: 'pp, ff' },
        { label: 'Range / position', value: 'Hand positions starting to move up the neck more often, ranges becoming more extended' },
        { label: 'Keys and chords', value: 'As previous grades, introducing three-note power chords, half/partial barre chords, occasional use of full barre chords' },
        { label: 'Part writing', value: 'More freely melodic writing featuring idiomatic guitar techniques, more variety in accompaniment writing' },
        { label: OTHER, value: 'Fingerstyle, quartertone, semitone and tone bends, vibrato, distortion/overdrive/crunch, crossed noteheads, hammer-on/pull-off' },
        { label: 'Improvisation', value: 'Basic cont. sim. rhythmic improvisation on a chord sequence' },
      ],
      [
        { label: 'Rhythmic values', value: 'All rhythmic values' },
        { label: 'Syncopation', value: 'Syncopation becoming a more important feature' },
        { label: 'Time signatures', value: TS_G4 },
        { label: 'Range / position', value: 'Further extension of range and position, introducing faster shifts' },
        { label: 'Keys and chords', value: 'Wider range of keys and chords, more regular use of full barre chords' },
        { label: 'Part writing', value: 'Mix of riffs, accompaniment and melody — varied part writing' },
        { label: OTHER, value: 'Heavy distortion, change between clean and distortion within song, slurred octaves' },
        { label: 'Improvisation', value: 'Slightly more complex cont. sim. improvisation, four-bar improvised solos' },
      ],
      [
        { label: 'Syncopation', value: 'Syncopation now a regular feature' },
        { label: 'Time signatures', value: 'Occasional irregular time signatures, more frequent changes of time signature' },
        { label: 'Range / position', value: 'Range and position as appropriate to demands of the music, including fast shifts' },
        { label: 'Keys and chords', value: 'Any key' },
        { label: 'Part writing', value: 'Part writing now fully varied, with written or improvised lead sections, riffs, melody and accompaniment of different textures' },
        { label: OTHER, value: 'Mandolin-style picking, more sophistication with sounds, effects and bends' },
        { label: 'Improvisation', value: 'Improvised solos of about eight bars' },
      ],
      [
        { label: 'Syncopation', value: 'Syncopation becoming complex in places' },
        { label: 'Time signatures', value: 'More use of irregular time signatures' },
        { label: OTHER, value: 'Controlled phrasing including at slow tempos — controlled use of different bends, pinch harmonics, flanger, phase, chorus, wah-wah' },
        { label: 'Improvisation', value: 'Improvised solos of about 12 bars' },
      ],
      [
        { label: 'Syncopation', value: 'More frequent use of complex syncopation' },
        { label: 'Improvisation', value: 'Improvised solos of about 16 bars' },
      ],
      [
        { label: 'Syncopation', value: 'Complex syncopation throughout' },
        { label: OTHER, value: 'Any standard guitar technique' },
        { label: 'Improvisation', value: 'Multiple improvised solos, any length' },
      ],
    ],
  },
  {
    key: 'bass',
    name: 'Bass',
    notes: [],
    grades: [
      [
        { label: 'Rhythmic values', value: 'Simple rhythms — mainly crotchet-based lines or repeated patterns, with some use of dotted crotchets, quavers, minims, semibreves and equivalent rests' },
        { label: 'Syncopation', value: 'Very simple use if featured in repeating patterns' },
        { label: 'Time signatures', value: TS_INITIAL },
        { label: 'Dynamics', value: DYN_INITIAL },
        { label: 'Range / position', value: 'Most songs within an octave, occasional use of a wider range if straightforward, basic hand positions' },
        { label: 'Keys', value: 'Keys that lend themselves to open-string bass lines (eg A major), otherwise up to one sharp or flat' },
        { label: 'Part writing', value: 'Repeated notes, stepwise movement, simple leaps of 3rds, 4ths and 5ths' },
        { label: 'Improvisation', value: NONE },
      ],
      [
        { label: 'Rhythmic values', value: 'As Initial but with slightly more variety and complexity' },
        { label: 'Syncopation', value: 'More frequent use, offbeat quavers' },
        { label: 'Time signatures', value: 'As Initial' },
        { label: 'Dynamics', value: DYN_G1 },
        { label: 'Range / position', value: 'As Initial' },
        { label: 'Keys', value: 'As Initial' },
        { label: 'Part writing', value: 'Faster repeated notes, octave leaps' },
        { label: OTHER, value: 'Staccato, accents, pause on last note' },
        { label: 'Improvisation', value: NONE },
      ],
      [
        { label: 'Rhythmic values', value: 'More varied rhythms: longer notes featuring alongside semiquaver patterns, more rests and silent bars' },
        { label: 'Syncopation', value: 'Slightly more complex than Grade 1, involving semiquavers' },
        { label: 'Time signatures', value: TS_G2 },
        { label: 'Dynamics', value: 'Crescendo/decrescendo within phrases, greater use of contrasts' },
        { label: 'Range / position', value: 'Range of an octave and a half, position slightly more varied than Grade 1' },
        { label: 'Keys', value: 'Up to three sharps/flats' },
        { label: 'Part writing', value: 'More melodic, alternating between straight bass line and slightly more complex fills, decorated lines' },
        { label: OTHER, value: 'Slides' },
        { label: 'Improvisation', value: NONE },
      ],
      [
        { label: 'Rhythmic values', value: 'Faster repeated notes, not always in regular patterns' },
        { label: 'Syncopation', value: 'Subtle syncopation an element of some or most phrases' },
        { label: 'Time signatures', value: TS_G3 },
        { label: 'Dynamics', value: 'pp, ff' },
        { label: 'Range / position', value: 'Songs can span two octaves, hand positions starting to move up the neck more often, ranges becoming more extended' },
        { label: 'Keys', value: 'As Grade 2' },
        { label: 'Part writing', value: 'As Grade 2' },
        { label: OTHER, value: 'Hammer-on, pull-off' },
        { label: 'Improvisation', value: 'Basic cont. sim. rhythmic improvisation on a chord sequence' },
      ],
      [
        { label: 'Rhythmic values', value: 'All rhythmic values' },
        { label: 'Syncopation', value: 'Syncopation becoming a more important feature' },
        { label: 'Time signatures', value: TS_G4 },
        { label: 'Range / position', value: 'Further extension of range and position, introducing faster shifts' },
        { label: 'Keys', value: 'As previous grades' },
        { label: 'Part writing', value: 'Faster repeated notes, more lyrical writing with more variety of colour and attack, starting to explore more foreground/background roles' },
        { label: 'Improvisation', value: 'Slightly more complex cont. sim. improvisation, four-bar improvised solos' },
      ],
      [
        { label: 'Syncopation', value: 'Now a regular feature' },
        { label: 'Time signatures', value: 'Irregular time signatures, more frequent changes of time signature' },
        { label: 'Range / position', value: 'Range and position as appropriate to demands of the music, including fast shifts' },
        { label: 'Keys', value: 'Up to four sharps/flats' },
        { label: 'Part writing', value: 'Two-note power chords, written-out solo breaks, wider leaps (octave and a half), wider variety of articulation' },
        { label: OTHER, value: 'Bends, distortion, cross-head notes (LH muted), ring, occasional basic slap technique' },
        { label: 'Improvisation', value: 'Improvised solos of about eight bars' },
      ],
      [
        { label: 'Syncopation', value: 'Complex in places' },
        { label: 'Keys', value: 'Any key' },
        { label: 'Part writing', value: 'Two-note chords spanning a 4th or other intervals that are similarly manageable at this level, three-note power chords, wide leaps and varied writing now a constant feature, parallel octaves/10ths/12ths with basic rhythmic independence between the two lines (eg minims in the lower part, dotted crotchet/quaver in the upper part), trills' },
        { label: OTHER, value: 'Slides and slurs (hammer-on, pull-off) appearing more regularly, slap technique' },
        { label: 'Improvisation', value: 'Improvised solos of about 12 bars' },
      ],
      [
        { label: 'Syncopation', value: 'More frequent use of complex syncopation' },
        { label: 'Part writing', value: 'Large leaps, playing in parallel intervals (eg 3rds)' },
        { label: OTHER, value: 'More complex picking patterns; quarter-tone bends; more rhythmically complex independent lines, including with the top part playing parallel intervals over a single line lower part; trill markings, including one note of a double stop; ‘endurance’ semiquaver passages; percussive effects; cross-string hammer-ons' },
        { label: 'Improvisation', value: 'Improvised solos of about 16 bars' },
      ],
      [
        { label: 'Syncopation', value: 'Complex syncopation throughout' },
        { label: 'Part writing', value: 'Further development of complexity introduced at earlier grades, very varied and often melodic writing requiring considerable agility' },
        { label: OTHER, value: 'Long trills, artificial harmonics' },
        { label: 'Improvisation', value: 'Multiple improvised solos, any length' },
      ],
    ],
  },
  {
    key: 'drums',
    name: 'Drums',
    notes: [
      'Initial to Grade 6 can be played on an electric or an acoustic kit. From Grade 7 an acoustic kit must be used. Exam centres only have to provide an acoustic kit.',
    ],
    grades: [
      [
        { label: 'Rhythmic values', value: 'Predominantly crotchet and quaver plus equivalent rests, occasional semiquavers' },
        { label: 'Dynamics', value: DYN_INITIAL },
        { label: 'Syncopation', value: NONE },
        { label: 'Time signatures', value: TS_INITIAL },
        { label: 'Kit / beaters', value: 'Full use of five-piece drum kit with sticks' },
        { label: 'Independence', value: 'Basic right foot independence only' },
        { label: 'Techniques', value: 'Cross stick, snares on/off, short notated solo breaks/fills, trashy/tight hi hat' },
        { label: 'Improvisation', value: NONE },
      ],
      [
        { label: 'Rhythmic values', value: 'Dotted crotchet, semiquaver (more frequent use), equivalent rests' },
        { label: 'Dynamics', value: 'p, mp, mf, f with occasional hairpins and crescendo/diminuendo' },
        { label: 'Other directions / markings', value: 'Accent, slash notation/cont. sim.' },
        { label: 'Syncopation', value: 'Limited — only very simple/repetitive quaver off-beats' },
        { label: 'Time signatures', value: 'As Initial' },
        { label: 'Kit / beaters', value: 'As Initial' },
        { label: 'Independence', value: 'As Initial' },
        { label: 'Techniques', value: 'As Initial' },
        { label: 'Improvisation', value: NONE },
      ],
      [
        { label: 'Rhythmic values', value: 'Dotted quavers, ties' },
        { label: 'Dynamics', value: 'As Grade 1, but with crescendos/decrescendos within phrases and greater use of contrasts' },
        { label: 'Other directions / markings', value: 'Subito dynamics' },
        { label: 'Syncopation', value: 'More quaver off-beats' },
        { label: 'Time signatures', value: TS_G2 },
        { label: 'Kit / beaters', value: 'Brushes (non swish patterns), hot rods/rutes' },
        { label: 'Independence', value: 'Basic right-hand independence around kit' },
        { label: 'Techniques', value: 'Open/closed hi hat, fills containing grace note rudiments, very simple swing patterns' },
        { label: 'Improvisation', value: 'Occasional ad lib. fills, cont. sim. with minor variation' },
      ],
      [
        { label: 'Rhythmic values', value: 'Semiquaver rest, semiquaver-quaver-semiquaver figure, quaver triplet, semiquaver triplet, more complex tied rhythms' },
        { label: 'Dynamics', value: 'pp, ff' },
        { label: 'Other directions / markings', value: 'Marcato accent, phrasing marks above the stave' },
        { label: 'Syncopation', value: 'Simple semiquaver off-beats' },
        { label: 'Time signatures', value: TS_G3 },
        { label: 'Kit / beaters', value: 'Soft mallets' },
        { label: 'Independence', value: 'Simple semiquaver independence in snare and bass drum' },
        { label: 'Techniques', value: 'Simple rolls, 16-beat grooves on hi hat (hand to hand)' },
        { label: 'Improvisation', value: 'Ad lib. fills appearing more often' },
      ],
      [
        { label: 'Rhythmic values', value: 'Demisemiquaver, more complex use of semiquaver triplet' },
        { label: 'Dynamics', value: 'Any, as relevant to the music' },
        { label: 'Other directions / markings', value: 'Any, as relevant to the music' },
        { label: 'Syncopation', value: 'Slightly more than Grade 3, syncopation in fills' },
        { label: 'Time signatures', value: TS_G4 },
        { label: 'Kit / beaters', value: 'Brushes' },
        { label: 'Independence', value: 'Slightly busier than Grade 3' },
        { label: 'Techniques', value: 'Ghost notes, simple scrub/swish for brushes, 3 against 2' },
        { label: 'Improvisation', value: 'Four-bar improvised solos' },
      ],
      [
        { label: 'Rhythmic values', value: 'Any' },
        { label: 'Syncopation', value: 'Free semiquaver/quaver triplet syncopation across the kit' },
        { label: 'Time signatures', value: 'Occasional irregular time signatures, more frequent changes of time signature' },
        { label: 'Independence', value: 'Simple left foot (hi hat with foot) independence' },
        { label: 'Techniques', value: 'Faster hi hat patterns may require Moeller/multiple motion, strokes, solo breaks around phrasing/ensemble hits, full brush swing pattern, longer interpretive cont. sim. passages' },
        { label: 'Improvisation', value: '8-bar improvised solos' },
      ],
      [
        { label: 'Time signatures', value: 'More use of irregular time signatures' },
        { label: 'Kit / beaters', value: 'Double bass-drum pedal as an option for fast moving BD patterns' },
        { label: 'Independence', value: 'More complex left hand off-beats under right-hand 16th patterns' },
        { label: 'Techniques', value: 'Faster, rebounded bass drum strokes, choked hi hat' },
        { label: 'Improvisation', value: '12-bar improvised solos' },
      ],
      [
        { label: 'Kit / beaters', value: 'Double bass-drum pedal' },
        { label: 'Techniques', value: 'Double-pedal patterns, faster double-stroke rolls on toms, more left hand lead around the drums' },
        { label: 'Improvisation', value: '16-bar improvised solos' },
      ],
      [
        { label: 'Independence', value: 'More complex left foot independence, hi hat or bass drum' },
        { label: 'Techniques', value: 'Extended solo sections, more challenging double-pedal writing, varied tone production' },
        { label: 'Improvisation', value: 'Multiple improvised solos, any length' },
      ],
    ],
  },
  {
    key: 'keyboards',
    name: 'Keyboards',
    notes: [
      'The exam room has either a digital piano or an acoustic piano (ask the centre which), or you can bring your own keyboard. Pick an own-choice song whose style works on the instrument you will play.',
    ],
    grades: [
      [
        { label: 'Rhythmic values', value: 'Simple, intuitive rhythms including crotchets, quavers, semibreves, dotted minims, dotted crotchets and ties where appropriate; equivalent rests; swung quavers' },
        { label: 'Syncopation', value: 'Simple occasional use where appropriate to the music' },
        { label: 'Time signatures', value: TS_INITIAL },
        { label: 'Dynamics', value: DYN_INITIAL },
        { label: 'Range', value: 'Hands together, moving between different parts of the keyboard but with time to move; some simple changes of hand position' },
        { label: 'Keys', value: 'Up to one sharp or flat' },
        { label: 'Part writing', value: 'Legato playing; simple melodic exchange between the hands, otherwise very basic LH and spare texture; occasional chromaticism, for example where this fits with a blues scale; two-note chords in RH and occasionally in LH' },
        { label: 'Improvisation', value: NONE },
      ],
      [
        { label: 'Rhythmic values', value: 'Occasional semiquavers' },
        { label: 'Syncopation', value: 'Occasional semiquaver syncopation' },
        { label: 'Time signatures', value: 'As Initial' },
        { label: 'Dynamics', value: DYN_G1 },
        { label: 'Range', value: 'More frequent movement between hand positions, some finger extension beyond standard positions' },
        { label: 'Keys', value: 'Up to two sharps/flats' },
        { label: 'Part writing', value: 'Occasional three-note chords, more use of repeated notes and two-note chords, repeated RH accompaniment patterns, still a simple LH' },
        { label: OTHER, value: 'Accents, tenuto' },
        { label: 'Improvisation', value: NONE },
      ],
      [
        { label: 'Rhythmic values', value: 'Slight increase in complexity from Grade 1' },
        { label: 'Syncopation', value: 'More frequent use' },
        { label: 'Time signatures', value: 'Adds 2/4; occasional changes of time signature if intuitive' },
        { label: 'Dynamics', value: 'Same range as Grade 1 but with crescendo/decrescendo within phrases and greater use of contrasts' },
        { label: 'Range', value: 'Hands beginning to travel beyond fixed hand positions, contained within a range two octaves either side of middle C, occasional stretch to octave' },
        { label: 'Keys', value: 'Up to three sharps/flats, simple key changes' },
        { label: 'Part writing', value: 'More clearly defined legato and accented/staccato contrasts, one hand can play up to four-note chords but mostly two- or three-note chords, more silent/empty bars as appropriate, hands becoming more independent with more complex LH' },
        { label: OTHER, value: 'Glissando; grace notes' },
        { label: 'Improvisation', value: NONE },
      ],
      [
        { label: 'Rhythmic values', value: 'Semiquavers more frequent in accompaniment and melodic material' },
        { label: 'Syncopation', value: 'As Grade 2' },
        { label: 'Time signatures', value: TS_G3 },
        { label: 'Dynamics', value: 'pp, ff' },
        { label: 'Range', value: 'Extending further into the ledger lines below bass clef stave' },
        { label: 'Keys', value: 'Up to four sharps/flats' },
        { label: 'Part writing', value: 'Faster repeated notes, more irregular accompaniment patterns, greater independence of hands, more textural variety within songs if musically appropriate, melody over sustained notes within one hand' },
        { label: 'Improvisation', value: NONE },
      ],
      [
        { label: 'Rhythmic values', value: 'All rhythmic values including triplets and duplets' },
        { label: 'Syncopation', value: 'Syncopation becoming a more important feature' },
        { label: 'Time signatures', value: TS_G4 },
        { label: 'Dynamics', value: 'All dynamics' },
        { label: 'Range', value: 'Approaching full range' },
        { label: 'Keys', value: 'As Grade 3' },
        { label: 'Part writing', value: 'Octave stretch, thirds in one hand, passing running passages between hands' },
        { label: OTHER, value: 'Ornaments, notated pedal use' },
        { label: 'Improvisation', value: 'Improvised solos of about four bars, improvised fills and accompaniments of up to four bars' },
      ],
      [
        { label: 'Syncopation', value: 'Syncopation now a regular feature' },
        { label: 'Time signatures', value: 'Irregular time signatures, more frequent changes of time signature' },
        { label: 'Range', value: 'Full use of range' },
        { label: 'Keys', value: 'Any key as appropriate to the music, more significant key changes' },
        { label: 'Part writing', value: 'More challenging passagework in both hands, together or moving independently, repeated 6ths, fast repeated notes, layered accompaniments requiring more textural sensitivity' },
        { label: OTHER, value: 'Spread chords' },
        { label: 'Improvisation', value: 'Improvised solos of about eight bars, improvised fills and accompaniments of up to eight bars' },
      ],
      [
        { label: 'Syncopation', value: 'Syncopation becoming complex in places' },
        { label: 'Melodic writing and intervals', value: 'Greater frequency of four-note chords, flowing semiquaver passages, denser chordal accompaniments with a stretch up to one octave in both hands' },
        { label: 'Improvisation', value: 'Improvised fills, solos and accompaniments up to 12 bars' },
      ],
      [
        { label: 'Syncopation', value: 'More frequent use of complex syncopation' },
        { label: 'Melodic writing and intervals', value: 'Octaves in both hands, including melodic writing and shaped phrases; extended parts featuring lots of different textures and keyboard roles' },
        { label: 'Improvisation', value: 'Improvised fills, solos and accompaniments up to 16 bars' },
      ],
      [
        { label: 'Syncopation', value: 'Complex syncopation can appear throughout' },
        { label: 'Melodic writing and intervals', value: 'Five-note chords (in one hand); parts show a high level of keyboard versatility, with most parts containing an aspect of improvising, melodic or accompaniment passagework, layered accompaniments, etc' },
        { label: 'Improvisation', value: 'Multiple improvised solos, fills and accompaniments of any length' },
      ],
    ],
  },
  {
    key: 'vocals',
    name: 'Vocals',
    notes: [
      'For song 2 only, a singer can choose a song in another language. Bring a copy in the original language and an English translation of the lyrics for the examiner.',
      'A singer can accompany themselves live on any instrument.',
      'If the style leans on spoken or belted sections (rap, metal, a story-telling ballad), there must still be enough singing to show the techniques for the grade.',
      'Microphones in face-to-face exams: at Grades 1–3 a mic is advised for song 2 but not compulsory. At Grades 4–5 it is advised unless song 2 is sung with live piano or guitar. At Grades 6–8 it is required unless song 2 is sung with live piano or guitar. In digital exams a mic is optional.',
    ],
    grades: [
      [
        { label: 'Rhythmic values', value: 'Simple rhythms, can include swung quavers' },
        { label: 'Syncopation', value: 'Occasional simple syncopation' },
        { label: 'Time signatures', value: TS_INITIAL },
        { label: 'Dynamics', value: DYN_INITIAL },
        { label: 'Range and register', value: 'No more than an octave, within one register' },
        { label: 'Keys', value: 'To suit vocal range — normally no more than one or two sharps or flats, can include modal tonality' },
        { label: 'Melodic writing and intervals', value: 'Simple melodies — mainly stepwise movement within phrases' },
        { label: OTHER, value: 'Slurs and legato phrasing, simple breath control through balanced phrase lengths' },
        { label: 'Accompaniment', value: 'Supportive backing with clear entries guided by accompaniment' },
        { label: 'Improvisation', value: NONE },
      ],
      [
        { label: 'Rhythmic values', value: 'More rhythmic variety — triplets can appear but rhythms still mostly simple and straightforward' },
        { label: 'Syncopation', value: 'Simple syncopation featuring more often' },
        { label: 'Time signatures', value: 'As Initial' },
        { label: 'Dynamics', value: DYN_G1 },
        { label: 'Range and register', value: 'As Initial' },
        { label: 'Keys', value: 'Occasional use of up to four sharps/flats if musically appropriate, but mainly maximum two sharps/flats' },
        { label: 'Melodic writing and intervals', value: 'Wider melodic leaps up to a 6th used sparingly' },
        { label: OTHER, value: 'Mainly legato singing with some more accented and articulated phrases, blue notes' },
        { label: 'Accompaniment', value: 'Relatively supportive backing' },
        { label: 'Improvisation', value: NONE },
      ],
      [
        { label: 'Rhythmic values', value: 'Wider range of rhythmic variety, occasional complexity and unexpected rhythmic patterns within broadly straightforward rhythms' },
        { label: 'Syncopation', value: 'Syncopation becoming a standard part of melodic writing' },
        { label: 'Time signatures', value: TS_G2 },
        { label: 'Dynamics', value: 'As Grade 1 but with crescendos/decrescendos within phrases and greater use of contrasts' },
        { label: 'Range and register', value: 'Up to a 10th, use of both lower and upper registers but no swift changes between the two' },
        { label: 'Keys', value: 'Up to four sharps/flats' },
        { label: 'Melodic writing and intervals', value: 'Occasional leaps up to a 7th, intervals up to a 4th a standard part of melodic writing' },
        { label: OTHER, value: 'Long notes and sustained phrases, chest voice or lower range with projection' },
        { label: 'Accompaniment', value: 'Relatively supportive with some rhythmic independence' },
        { label: 'Improvisation', value: NONE },
      ],
      [
        { label: 'Rhythmic values', value: 'Increasing rhythmic complexity and variation' },
        { label: 'Syncopation', value: 'Can be present throughout' },
        { label: 'Time signatures', value: TS_G3 },
        { label: 'Dynamics', value: 'pp, ff, increased use of crescendo/diminuendo on longer phrases and long notes' },
        { label: 'Range and register', value: 'As Grade 2 but with occasional faster register changes' },
        { label: 'Keys', value: 'As Grade 2' },
        { label: 'Melodic writing and intervals', value: 'As Grade 2, leaps more frequent' },
        { label: OTHER, value: 'Grace notes, staccato phrases, vocal emphasis of consonants to enhance style/emotion in fast rhythms and in slow sustained ballads' },
        { label: 'Accompaniment', value: 'Relatively supportive accompaniment, more independent vocal entries, less guidance in accompaniment' },
        { label: 'Improvisation', value: NONE },
      ],
      [
        { label: 'Rhythmic values', value: 'All rhythms can be used, increasing in complexity as the level increases' },
        { label: 'Syncopation', value: 'Present throughout, increasing in complexity as the level increases' },
        { label: 'Time signatures', value: TS_G4 },
        { label: 'Dynamics', value: 'All dynamics' },
        { label: 'Range and register', value: 'Up to an 11th; moving between high and low registers more common, including fast changes' },
        { label: 'Keys', value: 'Any key, including key changes' },
        { label: 'Melodic writing and intervals', value: 'Octave leaps can be used, melodic writing can use any interval, occasional chromaticism' },
        { label: OTHER, value: 'Increasing complexity to test articulation and melodic agility' },
        { label: 'Accompaniment', value: 'Moderately supportive backing' },
        { label: 'Improvisation', value: 'Improvised solos of about four bars' },
      ],
      [
        { label: 'Time signatures', value: 'Irregular time signatures, more frequent changes of time signature' },
        { label: 'Range and register', value: 'Up to a 12th' },
        { label: 'Melodic writing and intervals', value: 'Increased chromaticism, use of pentatonic and blues scales' },
        { label: OTHER, value: 'Melisma' },
        { label: 'Accompaniment', value: 'Moderately supportive backing' },
        { label: 'Improvisation', value: 'Improvised solos of about eight bars' },
      ],
      [
        { label: 'Range and register', value: 'Up to a 13th' },
        { label: 'Melodic writing and intervals', value: 'Leaps of over an octave, technically more challenging downward leaps can appear' },
        { label: OTHER, value: 'Effects such as creaks, growls, slides, screams, glottal and theatrics/dramatisations; occasional rap elements' },
        { label: 'Accompaniment', value: 'Backing becoming independent' },
        { label: 'Improvisation', value: 'Improvised solos of about 12 bars' },
      ],
      [
        { label: 'Range and register', value: 'Up to a 14th' },
        { label: 'Melodic writing and intervals', value: 'Further development of downward leaps, a high level of agility and freedom of movement between registers' },
        { label: OTHER, value: 'Further development of techniques introduced at Grade 6, greater use of rap if musically appropriate' },
        { label: 'Accompaniment', value: 'Almost fully independent backing' },
        { label: 'Improvisation', value: 'Improvised solos of about 16 bars' },
      ],
      [
        { label: 'Range and register', value: 'Up to two octaves' },
        { label: 'Melodic writing and intervals', value: 'Vocal parts requiring advanced tonal and melodic control; leaps across the complete range if musically appropriate, including downward leaps' },
        { label: OTHER, value: 'Full range of techniques' },
        { label: 'Accompaniment', value: 'Backing can be fully independent' },
        { label: 'Improvisation', value: 'Multiple improvised solos of any length' },
      ],
    ],
  },
]

// The rows one grade's table shows: length and tempo first, then the
// instrument's own parameters.
export function gradeRows(instrument: OwnChoiceInstrument, gradeIndex: number): ParameterRow[] {
  const overview = GRADE_OVERVIEW[gradeIndex]
  return [
    { label: 'Length', value: overview.length },
    { label: 'Tempo', value: overview.tempo },
    ...instrument.grades[gradeIndex],
  ]
}

export interface OwnChoiceRule {
  title: string
  // HTML allowed (the glass card renders it).
  detail: string
}

export const OWN_CHOICE_RULES: OwnChoiceRule[] = [
  {
    title: 'Only song 2',
    detail: 'You play three songs. Song 1 comes from the Trinity book for the grade and song 3 must be one of the book’s three Technical Focus [TF] songs, both played to the official Trinity backing track. Only <strong>song 2</strong> can be an own choice. It is worth the same 25 marks whatever you choose.',
  },
  {
    title: 'What it can be',
    detail: 'Sheet music from a printed or online source, a cover version you have arranged yourself, or a song you wrote. Songs from Trinity’s 2015–2017 Rock &amp; Pop syllabus also count if they meet today’s parameters, and so do Trinity’s extra arrangements at trinityrock.com/extra-songs.',
  },
  {
    title: 'How it can be played',
    detail: 'Unaccompanied, to a backing track that leaves out your part (a home-made one is fine), or with one live musician playing or singing along, who can be your teacher. Guitarists, bassists, drummers and keyboard players can sing it too; singers can accompany themselves on any instrument.',
  },
  {
    title: 'No one approves it in advance',
    detail: 'Trinity says it <strong>cannot pre-approve</strong> own-choice songs. It is up to you and your teacher to check the song against the parameters for your instrument and grade below. Trinity’s own test: a suitable song will probably look and feel like one of the songs in the book.',
  },
  {
    title: 'Length',
    detail: 'Each grade has a length range (see the tables below). Go over the maximum and the examiner stops you there; near the top of the range they may stop you once they have heard enough. You can shorten a song by cutting an intro or outro, or dropping a verse.',
  },
  {
    title: 'Same standard as the book',
    detail: 'An own-choice song must be <strong>equivalent in standard</strong> to the book songs for the grade. The parameters are cumulative, so everything from lower grades still counts, but a song does not need to contain everything on the list.',
  },
]

// The four ways the own-choice music can be written out. Same for face-to-face
// and digital, so both columns show this one string.
export const OWN_CHOICE_FORMATS =
  'A lead sheet (lyrics, chords and melody line), a chord chart with lyrics, a full score in staff notation, or staff notation with TAB. Handwritten or computer generated is fine.'

// What to bring or upload for the OWN-CHOICE song, face-to-face vs digital.
// Whole-exam rules (one continuous video, etc.) do not belong here.
export const OWN_CHOICE_PAPERWORK: { topic: string; faceToFace: string; digital: string }[] = [
  {
    topic: 'Music for the examiner',
    faceToFace: 'One copy for the examiner with the candidate’s name and number on it. The examiner keeps it.',
    digital: 'Upload a scan or photo of the own-choice song’s music, with all of its pages in one file, and mark it as the own choice on the upload form.',
  },
  {
    topic: 'How the music can be written',
    faceToFace: OWN_CHOICE_FORMATS,
    digital: OWN_CHOICE_FORMATS,
  },
  {
    topic: 'The copy you play from',
    faceToFace: 'The original, or an authorised download with proof of purchase.',
    digital: 'The copy you play from must be in shot while you film, even if you play from memory. On a tablet, the watermark must be readable on screen.',
  },
  {
    topic: 'Backing track',
    faceToFace: 'Good quality MP3 on a USB stick or a CD, without your part on it.',
    digital: 'Good quality and without your part. Check the balance so you and the track are both clearly heard.',
  },
  {
    topic: 'Live accompanist',
    faceToFace: 'One musician, in the room for song 2 only.',
    digital: 'One musician, in the video for song 2.',
  },
]

// How to judge a song's standard against the book (Trinity blog, 23 Aug 2023).
export const OWN_CHOICE_CHECKLIST: string[] = [
  'How busy and syncopated the rhythms are',
  'Pitch range',
  'Intervals and leaps',
  'Chords and how they are voiced',
  'Phrasing and ornaments',
  'Dynamics',
  'Tempo',
  'Length',
]
