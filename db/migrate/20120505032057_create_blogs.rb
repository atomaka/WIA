class CreateBlogs < ActiveRecord::Migration
  def change
    create_table :blogs do |t|
      t.string :title
      t.text :body
      t.datetime :release
      
      t.timestamps
    end
  end
end
