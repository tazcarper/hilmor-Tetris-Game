/**
 * The ModeSelector is the screen where a player selects the mode.
 * It has its own controls, screen etc. and thus it makes sense to have
 * this in its own module.
 */
ig.module( 
	'game.modules.mode-selector'
)
.requires(
	'impact.font',
    'game.entities.block',
    'game.entities.block-container',
   
    'game.modules.field-manager'
)
.defines(function() {

    ModeSelector = ig.Class.extend({
        
        font: new ig.Font( 'media/bitdust2_16.font.png' ),
        futura: new ig.Font( 'media/future-24.png' ),
        bg: new ig.Image('media/titlescreenBig.png'),
        playBtn: new ig.Image('media/playBtn.png'),
        soundSelect: new ig.Sound( 'media/sounds/stage-select.mp3' ),
        
        /**
         * The "SELECT MODE" text blinks, we need a timer for that
         */
        modeSelectBlinkTimer: new ig.Timer(),
        
        /**
         * All available modes in the screen.
         */
        selectedModeIndex: 0,
        modes: ['A', 'B'],
                
        init: function() {
         console.log(this.bg.data);
         // this.bg.width = 960;

        },
             
        /**
         * Handle controls.
         */
        update: function() {
            // if (ig.input.pressed('left') || ig.input.pressed('down')) {
            //     this.selectedModeIndex = mmod(this.selectedModeIndex - 1, this.modes.length);
            //     this.soundSelect.play();
            // }
            // else if (ig.input.pressed('right') || ig.input.pressed('up')) {
            //     this.selectedModeIndex = mmod(this.selectedModeIndex + 1, this.modes.length);
            //     this.soundSelect.play();
            // }

            if (ig.input.pressed('enter') || ig.input.pressed('rotateLeft') || ig.input.pressed('rotateRight') ) {
                 this.soundSelect.play();
                ig.game.gameState = MyGame.state.STAGE_SELECT;
                console.log(MyGame.state.STAGE_SELECT);
            }
        },
                
        draw: function() {

            this.bg.draw(-270, 0);
            // blink the select stage font
            var blink = mmod(this.modeSelectBlinkTimer.delta(), 2); // speed of the non-showing state (1.1 = hidden only short)
            if (blink <= 1.5) {
               this.futura.draw("PRESS SPACEBAR", ig.global.TILESIZE * 15.5, ig.global.TILESIZE * 16.5, ig.Font.ALIGN.CENTER);
                
            }
         //   this.playBtn.draw(190,230);
            var xOffset = ig.global.TILESIZE;
        
            for (var i = 0; i < this.modes.length; i++) {
                xOffset += ig.global.TILESIZE * 1.5;
                var numberOut = this.modes[i];
                if (this.modes[i] === this.modes[this.selectedModeIndex]) {
                    if (numberOut == 'A'){
                        numberOut = '';
                    }
                    if (numberOut == 'B'){
                        numberOut = '';
                    } 
                    numberOut = ' ' + numberOut + ' ';
                }
                if (numberOut == 'A'){
                        numberOut = 'PLAY';
                    }
                    if (numberOut == 'B'){
                        numberOut = '';
                    } 
                this.font.draw(numberOut, (ig.global.TILESIZE*16.5) , (ig.global.TILESIZE*13.5) + ig.global.TILESIZE + xOffset, ig.Font.ALIGN.CENTER);
                
            }
        },
                
        getSelectedMode: function() {
            return this.modes[this.selectedModeIndex];
        }
        
    });
});
